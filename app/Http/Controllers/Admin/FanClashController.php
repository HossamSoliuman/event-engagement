<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrepareFanClashRoundRequest;
use App\Http\Requests\StartFanClashRoundRequest;
use App\Http\Requests\UpdateFanClashScreenSettingsRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FanClashMatchup;
use App\Models\FanClashRound;
use App\Models\FanClashScreen;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * The Fan Clash console: the controls that take a round through its steps on
 * the vidiwall (show the matchup, start, end, done), the matchup pool and the
 * rounds so far. Controls answer with the clash's status when called from the
 * page's script.
 */
class FanClashController extends Controller
{
    private const DEFAULT_SIDE_B_COLOR = '#3B82F6';

    private const VOTE_REVEALING = 'The vote winner is still being revealed on the vidiwall. Wait for it or hide it first.';

    private const LOTTERY_DRAWING = 'A lottery draw is still running on the vidiwall. Wait for its winner or hide it first.';

    public function index(Event $event): View
    {
        $matchups = $event->fanClashMatchups()->oldest('id')->get();
        $rounds = $event->fanClashRounds()->latest('id')->withCount('participants')->get();

        return view($this->indexView(), [
            'event' => $event,
            'matchups' => $matchups,
            'rounds' => $rounds,
            'screen' => $event->fanClashShow(),
            'screenStatus' => $this->screenStatus($event),
            'nextMatchupId' => $this->nextMatchupId($matchups, $rounds),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function screen(Event $event): JsonResponse
    {
        return response()->json($this->screenStatus($event));
    }

    /**
     * Puts a matchup on the vidiwall, so the room can scan in and pick a side
     * before the round starts.
     */
    public function prepare(PrepareFanClashRoundRequest $request, Event $event): JsonResponse|RedirectResponse
    {
        $screen = $event->fanClashShow();
        $this->rememberRoundLength($request, $screen);

        if ($screen->stage() !== FanClashScreen::STAGE_READY) {
            return $this->screenResponse($request, $event, 'A round is already on the console. Finish it first.', 409);
        }

        if ($blocked = $this->blockedReason($event)) {
            return $this->screenResponse($request, $event, $blocked, 409);
        }

        $snapshot = $this->resolveSnapshot($event, $request->validated());

        if ($snapshot === null) {
            return $this->screenResponse($request, $event, 'Pick a saved matchup, or type both contender names.', 422);
        }

        $round = $screen->prepare($snapshot);
        ActivityLog::record('fanclash.round_prepared', ['round_id' => $round->id], $event->id);

        return $this->screenResponse($request, $event, "{$round->side_a_name} vs {$round->side_b_name} is on the vidiwall. Fans can pick a side.");
    }

    /**
     * Counts the room in and starts the round on the vidiwall. From the start
     * of the console it puts the matchup up first.
     */
    public function start(StartFanClashRoundRequest $request, Event $event): JsonResponse|RedirectResponse
    {
        $screen = $event->fanClashShow();
        $this->rememberRoundLength($request, $screen);
        $stage = $screen->stage();

        if (! in_array($stage, [FanClashScreen::STAGE_READY, FanClashScreen::STAGE_MATCHUP], true) || $event->activeFanClashRound()) {
            return $this->screenResponse($request, $event, 'A round is already running. End it first.', 409);
        }

        if ($blocked = $this->blockedReason($event)) {
            return $this->screenResponse($request, $event, $blocked, 409);
        }

        if ($stage === FanClashScreen::STAGE_READY) {
            $snapshot = $this->resolveSnapshot($event, $request->validated());

            if ($snapshot === null) {
                return $this->screenResponse($request, $event, 'Pick a saved matchup, or type both contender names.', 422);
            }

            $screen->prepare($snapshot);
        }

        $screen->start();
        ActivityLog::record('fanclash.round_started', ['round_id' => $screen->fan_clash_round_id], $event->id);

        return $this->screenResponse($request, $event, 'The round is on.');
    }

    /**
     * Ends the running round now; the winner comes from the taps so far.
     */
    public function end(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $screen = $event->fanClashShow();

        if ($screen->stage() === FanClashScreen::STAGE_LIVE) {
            $screen->end();
        } elseif ($round = $event->activeFanClashRound()) {
            $round->finish();
        } else {
            return $this->screenResponse($request, $event, 'No round is running.', 422);
        }

        ActivityLog::record('fanclash.round_ended', [], $event->id);

        return $this->screenResponse($request, $event, 'Round over. The winner stays on the vidiwall until you are done.');
    }

    /**
     * Gives the running round ten more seconds.
     */
    public function extend(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $screen = $event->fanClashShow();

        if ($screen->stage() !== FanClashScreen::STAGE_LIVE) {
            return $this->screenResponse($request, $event, 'No round is running.', 422);
        }

        $screen->extend();

        return $this->screenResponse($request, $event, FanClashScreen::EXTEND_SECONDS.' more seconds to tap.');
    }

    /**
     * Puts the clash back on the vidiwall.
     */
    public function show(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $screen = $event->fanClashShow();

        if ($screen->stage() === FanClashScreen::STAGE_READY) {
            return $this->screenResponse($request, $event, 'Put a matchup up first.', 422);
        }

        if ($blocked = $this->blockedReason($event)) {
            return $this->screenResponse($request, $event, $blocked, 409);
        }

        $screen->show();

        return $this->screenResponse($request, $event, 'Fan Clash is on the vidiwall.');
    }

    /**
     * Takes the clash off the vidiwall; the round carries on.
     */
    public function hide(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->fanClashShow()->hide();

        return $this->screenResponse($request, $event, 'Fan Clash is off the vidiwall.');
    }

    /**
     * Done with the round: off the vidiwall, and the playlist carries on.
     */
    public function finish(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $screen = $event->fanClashShow();

        if ($screen->stage() === FanClashScreen::STAGE_LIVE) {
            return $this->screenResponse($request, $event, 'The round is still running. End it first.', 409);
        }

        $screen->finish();

        return $this->screenResponse($request, $event, 'Done. The playlist is back on the vidiwall.');
    }

    /**
     * Takes down a matchup that has not started, so another one can be picked.
     */
    public function cancel(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $screen = $event->fanClashShow();

        if ($screen->stage() !== FanClashScreen::STAGE_MATCHUP) {
            return $this->screenResponse($request, $event, 'Only a matchup that has not started can be changed.', 409);
        }

        $screen->cancel();

        return $this->screenResponse($request, $event, 'Matchup taken down. Pick another one.');
    }

    public function updateScreen(UpdateFanClashScreenSettingsRequest $request, Event $event): JsonResponse|RedirectResponse
    {
        $event->fanClashShow()->update($request->settings());

        if ($request->expectsJson()) {
            return $this->screenResponse($request, $event);
        }

        return redirect()->route($this->routePrefix().'fanclash.index', $event)->with('success', 'Screen options saved.');
    }

    public function storeMatchup(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validateMatchup($request);

        unset($data['side_a_image'], $data['side_b_image'], $data['sponsor_logo']);
        $data['event_id'] = $event->id;
        $data['side_a_image_path'] = $this->storeImage($request, 'side_a_image');
        $data['side_b_image_path'] = $this->storeImage($request, 'side_b_image');
        $data['sponsor_logo_path'] = $this->storeImage($request, 'sponsor_logo');

        FanClashMatchup::create($data);

        ActivityLog::record('fanclash.matchup_added', ['event' => $event->name], $event->id);

        return back()->with('success', 'Matchup added.');
    }

    public function updateMatchup(Request $request, Event $event, FanClashMatchup $fanClashMatchup): RedirectResponse
    {
        $data = $this->validateMatchup($request);

        unset($data['side_a_image'], $data['side_b_image'], $data['sponsor_logo']);
        $data['is_active'] = $request->boolean('is_active', true);

        $this->applyImageUpdate($request, $fanClashMatchup, $data, 'side_a_image', 'side_a_image_path');
        $this->applyImageUpdate($request, $fanClashMatchup, $data, 'side_b_image', 'side_b_image_path');
        $this->applyImageUpdate($request, $fanClashMatchup, $data, 'sponsor_logo', 'sponsor_logo_path');

        $fanClashMatchup->update($data);

        return back()->with('success', 'Matchup updated.');
    }

    public function destroyMatchup(Event $event, FanClashMatchup $fanClashMatchup): RedirectResponse
    {
        foreach (['side_a_image_path', 'side_b_image_path', 'sponsor_logo_path'] as $path) {
            if ($fanClashMatchup->$path) {
                Storage::disk('media')->delete($fanClashMatchup->$path);
            }
        }

        $fanClashMatchup->delete();

        return back()->with('success', 'Matchup removed.');
    }

    /**
     * Deletes a round and its taps, for clearing out test rounds.
     */
    public function destroyRound(Event $event, FanClashRound $fanClashRound): RedirectResponse
    {
        if ($event->fanClashShow()->fan_clash_round_id === $fanClashRound->id) {
            return back()->with('error', 'This round is on the console. Finish it first.');
        }

        $fanClashRound->delete();

        return back()->with('success', 'Round deleted.');
    }

    public function export(Event $event): Response
    {
        $rounds = $event->fanClashRounds()->with('participants')->get();

        $csv = "Round ID,Status,Category,Side A,Side B,A Taps,B Taps,Winner,Started At,Session Token,Side,Taps\n";

        foreach ($rounds as $round) {
            foreach ($round->participants as $p) {
                $csv .= implode(',', [
                    $round->id,
                    '"'.$round->status.'"',
                    '"'.addslashes((string) $round->category).'"',
                    '"'.addslashes($round->side_a_name).'"',
                    '"'.addslashes($round->side_b_name).'"',
                    $round->side_a_taps,
                    $round->side_b_taps,
                    '"'.($round->winner_side ?? '').'"',
                    '"'.($round->started_at?->format('Y-m-d H:i') ?? '').'"',
                    '"'.$p->session_token.'"',
                    $p->side,
                    $p->taps,
                ])."\n";
            }
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"fanclash-{$event->slug}.csv\"",
        ]);
    }

    protected function indexView(): string
    {
        return 'admin.fanclash.index';
    }

    protected function routePrefix(): string
    {
        return 'admin.';
    }

    /**
     * Keeps the round length picked on the console for this and later rounds.
     */
    private function rememberRoundLength(Request $request, FanClashScreen $screen): void
    {
        if ($request->filled('duration_seconds') && $request->integer('duration_seconds') !== $screen->round_seconds) {
            $screen->update(['round_seconds' => $request->integer('duration_seconds')]);
        }
    }

    /**
     * Why the clash may not take the screen right now: a winner still on the
     * way from the vote or the lottery.
     */
    private function blockedReason(Event $event): ?string
    {
        return match (true) {
            (bool) $event->votingScreen?->setRelation('event', $event)->isRevealPending() => self::VOTE_REVEALING,
            (bool) $event->lotteryScreen?->setRelation('event', $event)->isRevealPending() => self::LOTTERY_DRAWING,
            default => null,
        };
    }

    /**
     * The saved matchup after the one played last, so the console offers the
     * pool in order.
     *
     * @param  Collection<int, FanClashMatchup>  $matchups
     * @param  Collection<int, FanClashRound>  $rounds
     */
    private function nextMatchupId(Collection $matchups, Collection $rounds): ?int
    {
        $pool = $matchups->where('is_active', true)->values();

        if ($pool->isEmpty()) {
            return null;
        }

        $lastId = $rounds->first()?->fan_clash_matchup_id;
        $index = $lastId ? $pool->search(fn (FanClashMatchup $matchup) => $matchup->id === $lastId) : false;

        return $index === false ? $pool->first()->id : $pool->get(($index + 1) % $pool->count())->id;
    }

    private function screenResponse(Request $request, Event $event, ?string $message = null, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([...$this->screenStatus($event), 'message' => $message], $status);
        }

        return redirect()->route($this->routePrefix().'fanclash.index', $event)->with($status < 400 ? 'success' : 'error', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function screenStatus(Event $event): array
    {
        return [
            ...$event->fanClashShow()->controlStatus(),
            'blocked' => $this->blockedReason($event),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMatchup(Request $request): array
    {
        return $request->validate([
            'category' => 'nullable|string|max:60',
            'side_a_name' => 'required|string|max:60',
            'side_b_name' => 'required|string|max:60',
            'side_a_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'side_b_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'side_a_image' => 'nullable|image|max:2048',
            'side_b_image' => 'nullable|image|max:2048',
            'sponsor_logo' => 'nullable|image|max:2048',
        ]);
    }

    /**
     * The names, colours and artwork a new round keeps, from a saved matchup
     * or from two names typed on the console.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function resolveSnapshot(Event $event, array $data): ?array
    {
        if (! empty($data['fan_clash_matchup_id'])) {
            $matchup = $event->fanClashMatchups()->find($data['fan_clash_matchup_id']);

            if (! $matchup) {
                return null;
            }

            return [
                'fan_clash_matchup_id' => $matchup->id,
                'category' => ($data['category'] ?? '') ?: $matchup->category,
                'side_a_name' => $matchup->side_a_name,
                'side_b_name' => $matchup->side_b_name,
                'side_a_color' => $matchup->side_a_color ?: $event->fanClashAccentColor(),
                'side_b_color' => $matchup->side_b_color ?: self::DEFAULT_SIDE_B_COLOR,
                'side_a_image_path' => $matchup->side_a_image_path,
                'side_b_image_path' => $matchup->side_b_image_path,
                'sponsor_logo_path' => $matchup->sponsor_logo_path,
            ];
        }

        if (empty($data['side_a_name']) || empty($data['side_b_name'])) {
            return null;
        }

        return [
            'fan_clash_matchup_id' => null,
            'category' => ($data['category'] ?? '') ?: null,
            'side_a_name' => $data['side_a_name'],
            'side_b_name' => $data['side_b_name'],
            'side_a_color' => ($data['side_a_color'] ?? '') ?: $event->fanClashAccentColor(),
            'side_b_color' => ($data['side_b_color'] ?? '') ?: self::DEFAULT_SIDE_B_COLOR,
            'side_a_image_path' => null,
            'side_b_image_path' => null,
            'sponsor_logo_path' => null,
        ];
    }

    private function storeImage(Request $request, string $field): ?string
    {
        return $request->hasFile($field)
            ? $request->file($field)->store('fanclash', 'media')
            : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyImageUpdate(Request $request, FanClashMatchup $matchup, array &$data, string $field, string $column): void
    {
        if ($request->hasFile($field)) {
            if ($matchup->$column) {
                Storage::disk('media')->delete($matchup->$column);
            }
            $data[$column] = $request->file($field)->store('fanclash', 'media');
        } elseif ($request->boolean($field.'_clear')) {
            if ($matchup->$column) {
                Storage::disk('media')->delete($matchup->$column);
            }
            $data[$column] = null;
        }
    }
}
