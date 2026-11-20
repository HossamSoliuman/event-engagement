<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackPageView;
use App\Models\Event;
use App\Models\FanClashParticipant;
use App\Models\FanClashRound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FanClashController extends Controller
{
    /**
     * The most taps per second a single phone could plausibly register. Used to
     * clamp incoming batches so a scripted client can't inflate the counters.
     */
    private const MAX_TAPS_PER_SECOND = 25;

    /**
     * Slack added to the elapsed-time ceiling to absorb one flush interval of
     * batch latency plus any client/server clock skew.
     */
    private const CLAMP_GRACE_SECONDS = 2;

    public function status(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $round = $event->activeFanClashRound();
        if ($round) {
            $round->finalizeIfExpired();
        }

        if ($round && $round->isActive()) {
            return response()->json([
                'status' => 'active',
                'round_id' => $round->id,
                'category' => $round->category,
                'side_a_name' => $round->side_a_name,
                'side_b_name' => $round->side_b_name,
                'side_a_color' => $round->side_a_color,
                'side_b_color' => $round->side_b_color,
                'duration_seconds' => $round->duration_seconds,
                'countdown_seconds' => $event->fanClashScreen?->countdown_seconds ?? 0,
                'started_at' => $round->started_at?->toIso8601String(),
                'starts_in_ms' => $round->startsInMs(),
                'remaining_ms' => min($round->duration_seconds * 1000, $round->remainingMs()),
                'side_a_taps' => $round->side_a_taps,
                'side_b_taps' => $round->side_b_taps,
                ...$round->clock(),
            ]);
        }

        $upNext = $event->fanClashScreen?->round;

        if ($upNext && $upNext->status === 'waiting') {
            return $this->withServerTime([
                'status' => 'ready',
                'round_id' => $upNext->id,
                'category' => $upNext->category,
                'side_a_name' => $upNext->side_a_name,
                'side_b_name' => $upNext->side_b_name,
                'side_a_color' => $upNext->side_a_color,
                'side_b_color' => $upNext->side_b_color,
                'duration_seconds' => $upNext->duration_seconds,
            ]);
        }

        $finished = $round && $round->isFinished()
            ? $round
            : $event->fanClashRounds()->where('status', 'finished')->latest()->first();

        if ($finished) {
            return $this->withServerTime($this->finishedPayload($finished));
        }

        return $this->withServerTime(['status' => 'waiting']);
    }

    public function tap(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'session_token' => 'required|string|max:64',
            'side' => 'required|in:a,b',
            'taps' => 'required|integer|min:0',
        ]);

        $round = $event->activeFanClashRound();

        if (! $round) {
            return response()->json(['error' => 'No active clash round.'], 422);
        }

        if ($round->finalizeIfExpired()) {
            return response()->json(['error' => 'Round has ended.', 'status' => 'finished'], 422);
        }

        if (! $round->hasStarted()) {
            return response()->json(['error' => 'Round has not started yet.', 'status' => 'active', 'starts_in_ms' => $round->startsInMs()], 422);
        }

        $elapsedSeconds = $round->started_at
            ? max(0.0, ((microtime(true) * 1000) - $round->started_at->getTimestampMs()) / 1000)
            : 0.0;
        $allowedSeconds = min((float) $round->duration_seconds, $elapsedSeconds + self::CLAMP_GRACE_SECONDS);
        $maxTaps = (int) ceil(self::MAX_TAPS_PER_SECOND * $allowedSeconds);

        $participant = FanClashParticipant::firstOrNew([
            'fan_clash_round_id' => $round->id,
            'session_token' => $data['session_token'],
        ]);

        if (! $participant->exists) {
            $participant->event_id = $event->id;
            $participant->visitor_id = $request->cookie(TrackPageView::COOKIE);
            $participant->side = $data['side'];
            $participant->taps = 0;
        }

        $cumulative = min($participant->taps + $data['taps'], $maxTaps);
        $delta = $cumulative - $participant->taps;

        if ($delta > 0 && ! $round->addTaps($participant->side, $delta)) {
            return response()->json(['error' => 'Round has ended.', 'status' => 'finished'], 422);
        }

        $participant->taps = $cumulative;
        $participant->save();

        $round->refresh();

        return response()->json([
            'status' => 'active',
            'side' => $participant->side,
            'your_taps' => $participant->taps,
            'side_a_taps' => $round->side_a_taps,
            'side_b_taps' => $round->side_b_taps,
            'remaining_ms' => min($round->duration_seconds * 1000, $round->remainingMs()),
            ...$round->clock(),
        ]);
    }

    /**
     * Every answer carries the server's time, so a phone keeps its copy of
     * the round's clock in step from the moment it opens the clash.
     *
     * @param  array<string, mixed>  $payload
     */
    private function withServerTime(array $payload): JsonResponse
    {
        return response()->json([...$payload, 'server_now_ms' => FanClashRound::nowMs()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function finishedPayload(FanClashRound $round): array
    {
        return [
            'status' => 'finished',
            'round_id' => $round->id,
            'category' => $round->category,
            'side_a_name' => $round->side_a_name,
            'side_b_name' => $round->side_b_name,
            'side_a_color' => $round->side_a_color,
            'side_b_color' => $round->side_b_color,
            'side_a_taps' => $round->side_a_taps,
            'side_b_taps' => $round->side_b_taps,
            'winner_side' => $round->winner_side,
            'sponsor_logo_url' => $round->sponsor_logo_url,
        ];
    }
}
