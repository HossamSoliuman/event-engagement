<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVotingScreenSettingsRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\VotingScreen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The voting console: the standings as they come in and the controls for
 * putting them on the vidiwall (live results, the winner reveal and the
 * podium). Screen controls answer with the show's status when called from the
 * page's script.
 */
class VotingAdminController extends Controller
{
    private const REVEAL_RUNNING = 'The winner is still being revealed on the vidiwall. Reveal it now or hide it first.';

    private const LOTTERY_RUNNING = 'A lottery draw is still running on the vidiwall. Wait for its winner or hide it first.';

    public function index(Event $event): View
    {
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

    public function close(Event $event): RedirectResponse
    {
        $event->update(['voting_closed' => true]);
        ActivityLog::record('voting.closed', [], $event->id);

        return back()->with('success', '🔒 Voting closed.');
    }

    public function reopen(Event $event): RedirectResponse
    {
        $event->update(['voting_closed' => false]);
        ActivityLog::record('voting.reopened', [], $event->id);

        return back()->with('success', '🔓 Voting reopened.');
    }

    public function reset(Event $event): RedirectResponse
    {
        $event->votes()->delete();
        $event->update(['voting_closed' => false]);
        $event->votingScreen?->setRelation('event', $event)->hide();
        ActivityLog::record('voting.reset', [], $event->id);

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

    public function updateScreen(UpdateVotingScreenSettingsRequest $request, Event $event): RedirectResponse
    {
        $event->votingShow()->update($request->settings());

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
     * Counts down to the winner on the vidiwall. Voting closes first when the
     * settings say so, and the reveal keeps the standings from that moment.
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

        if ($screen->close_on_reveal && ! $event->voting_closed) {
            $event->update(['voting_closed' => true]);
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
        $standings = $event->voteStandings();

        return [
            ...$event->votingShow()->controlStatus(),
            'blocked' => $event->lotteryScreen?->setRelation('event', $event)->isRevealPending() ? self::LOTTERY_RUNNING : null,
            'voting_closed' => (bool) $event->voting_closed,
            'total' => array_sum(array_column($standings, 'votes')),
            'standings' => $standings,
        ];
    }
}
