<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVotingScreenSettingsRequest;
use App\Http\Requests\UpdateVotingTimerRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\VotingScreen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The voting console: the standings as they come in and the controls that take
 * the vote through its steps on the vidiwall (start voting with the live
 * results, end it, reveal the winner, the podium, done). Controls answer with
 * the vote's status when called from the page's script.
 */
class VotingAdminController extends Controller
{
    private const REVEAL_RUNNING = 'The winner is still being revealed on the vidiwall. Reveal it now or hide it first.';

    private const LOTTERY_RUNNING = 'A lottery draw is still running on the vidiwall. Wait for its winner or hide it first.';

    public function index(Event $event): View
    {
        $event->closeVotingWhenTimeIsUp();
        $standings = $event->voteStandings();

        return view($this->indexView(), [
            'event' => $event,
            'standings' => $standings,
            'totalVotes' => array_sum(array_column($standings, 'votes')),
            'votes' => $event->votes()->latest()->paginate(30),
            'screen' => $event->votingShow(),
            'screenStatus' => $this->screenStatus($event),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    /**
     * Opens voting for the set time and puts the live results on the vidiwall.
     */
    public function start(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $screen = $event->votingShow();

        if ($screen->stage !== VotingScreen::STAGE_READY) {
            return $this->screenResponse($request, $event, 'This vote has already started. Start a new vote first.', 409);
        }

        if ($busy = $this->screenBusy($event)) {
            return $this->screenResponse($request, $event, $busy, 409);
        }

        if ($event->voteStandings() === []) {
            return $this->screenResponse($request, $event, 'Add athletes to the vote in the event settings first.', 422);
        }

        $screen->startVoting();
        ActivityLog::record('voting.started', ['minutes' => $screen->voting_minutes], $event->id);

        return $this->screenResponse($request, $event, 'Voting is open and on the vidiwall.');
    }

    public function close(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->closeVoting();
        ActivityLog::record('voting.closed', [], $event->id);

        if ($request->expectsJson()) {
            return $this->screenResponse($request, $event, 'Voting is closed.');
        }

        return back()->with('success', '🔒 Voting closed.');
    }

    public function reopen(Event $event): RedirectResponse
    {
        $event->openVoting();
        ActivityLog::record('voting.reopened', [], $event->id);

        return back()->with('success', '🔓 Voting reopened.');
    }

    /**
     * Gives the running voting timer another minute.
     */
    public function extend(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->closeVotingWhenTimeIsUp();

        if ($event->votingMsLeft() === null) {
            return $this->screenResponse($request, $event, 'Voting has no timer running.', 422);
        }

        $event->extendVoting(60);

        return $this->screenResponse($request, $event, 'One more minute to vote.');
    }

    public function updateTimer(UpdateVotingTimerRequest $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->votingShow()->update(['voting_minutes' => $request->integer('minutes')]);

        return $this->screenResponse($request, $event);
    }

    /**
     * Deletes every vote and goes back to the start for a new vote.
     */
    public function reset(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->votes()->delete();
        $event->openVoting();
        $event->votingShow()->restart();
        ActivityLog::record('voting.reset', [], $event->id);

        if ($request->expectsJson()) {
            return $this->screenResponse($request, $event, 'All votes cleared. Ready for a new vote.');
        }

        return back()->with('success', 'All votes cleared.');
    }

    public function export(Event $event): Response
    {
        $tallies = $event->getVoteTallies();
        $csv = "Candidate,Votes,Percentage\n";
        $total = array_sum(array_column($tallies, 'total'));
        foreach ($tallies as $t) {
            $pct = $total > 0 ? round(($t['total'] / $total) * 100, 1) : 0;
            $csv .= '"'.$t['candidate_name'].'",'.$t['total'].','.$pct."%\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"votes-{$event->slug}.csv\"",
        ]);
    }

    public function updateScreen(UpdateVotingScreenSettingsRequest $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->votingShow()->update($request->settings());

        if ($request->expectsJson()) {
            return $this->screenResponse($request, $event);
        }

        return redirect()->to(route($this->routePrefix().'voting.index', $event).'#vidiwall-show')
            ->with('success', 'Vidiwall show settings saved.');
    }

    public function screen(Event $event): JsonResponse
    {
        return response()->json($this->screenStatus($event));
    }

    public function showLive(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        if ($busy = $this->screenBusy($event)) {
            return $this->screenResponse($request, $event, $busy, 409);
        }

        if ($event->voteStandings() === []) {
            return $this->screenResponse($request, $event, 'Add athletes to the vote in the event settings first.', 422);
        }

        $event->votingShow()->showLive();

        return $this->screenResponse($request, $event, 'The live results are on the vidiwall.');
    }

    /**
     * Counts down to the winner on the vidiwall. Voting closes first, and the
     * reveal keeps the standings from that moment.
     */
    public function reveal(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        if ($busy = $this->screenBusy($event)) {
            return $this->screenResponse($request, $event, $busy, 409);
        }

        if (! $event->votes()->exists()) {
            return $this->screenResponse($request, $event, 'No votes yet, so there is no winner to reveal.', 422);
        }

        $screen = $event->votingShow();

        if (! $event->voting_closed) {
            $event->closeVoting();
            ActivityLog::record('voting.closed', [], $event->id);
        }

        $standings = $event->voteStandings();
        $screen->startReveal($standings);

        $winners = array_column(VotingScreen::winnersOf($standings), 'name');
        ActivityLog::record('voting.revealed', ['winners' => $winners], $event->id);

        return $this->screenResponse($request, $event, count($winners) > 1
            ? 'Revealing a tie on the vidiwall: '.implode(' and ', $winners).'.'
            : "Revealing {$winners[0]} on the vidiwall.");
    }

    public function showPodium(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        if ($busy = $this->screenBusy($event)) {
            return $this->screenResponse($request, $event, $busy, 409);
        }

        if (! $event->votes()->exists()) {
            return $this->screenResponse($request, $event, 'There is no podium until the first votes are in.', 422);
        }

        $event->votingShow()->showPodium();

        return $this->screenResponse($request, $event, 'The podium is on the vidiwall.');
    }

    public function revealNow(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->votingShow()->revealNow();

        return $this->screenResponse($request, $event);
    }

    public function hideScreen(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->votingShow()->hide();

        return $this->screenResponse($request, $event, 'The vote is off the vidiwall.');
    }

    /**
     * Ends the vote: off the vidiwall, and the playlist carries on.
     */
    public function finish(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->votingShow()->finish();

        return $this->screenResponse($request, $event, 'Done. The playlist is back on the vidiwall.');
    }

    protected function indexView(): string
    {
        return 'admin.voting.index';
    }

    protected function routePrefix(): string
    {
        return 'admin.';
    }

    /**
     * Why nothing new may take the screen right now: a winner still on the
     * way, from the vote or the lottery.
     */
    private function screenBusy(Event $event): ?string
    {
        return match (true) {
            (bool) $event->votingScreen?->setRelation('event', $event)->isRevealPending() => self::REVEAL_RUNNING,
            (bool) $event->lotteryScreen?->setRelation('event', $event)->isRevealPending() => self::LOTTERY_RUNNING,
            default => null,
        };
    }

    private function screenResponse(Request $request, Event $event, ?string $message = null, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([...$this->screenStatus($event), 'message' => $message], $status);
        }

        return redirect()->route($this->routePrefix().'voting.index', $event)->with($status < 400 ? 'success' : 'error', $message);
    }

    /**
     * The show on the vidiwall plus the standings, so an open console follows
     * the votes as they come in.
     *
     * @return array<string, mixed>
     */
    private function screenStatus(Event $event): array
    {
        $event->closeVotingWhenTimeIsUp();
        $standings = $event->voteStandings();

        return [
            ...$event->votingShow()->controlStatus(),
            'blocked' => $event->lotteryScreen?->setRelation('event', $event)->isRevealPending() ? self::LOTTERY_RUNNING : null,
            'voting_closed' => (bool) $event->voting_closed,
            'ends_in_ms' => $event->votingMsLeft(),
            'total' => array_sum(array_column($standings, 'votes')),
            'standings' => $standings,
        ];
    }
}
