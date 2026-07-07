<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FanClashMatchup;
use App\Models\FanClashRound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FanClashController extends Controller
{
    private const DEFAULT_SIDE_A_COLOR = '#FF3D00';

    private const DEFAULT_SIDE_B_COLOR = '#3B82F6';

    public function index(Event $event)
    {
        $matchups = $event->fanClashMatchups()->latest()->get();
        $rounds = $event->fanClashRounds()->latest()->withCount('participants')->get();
        $activeRound = $event->activeFanClashRound();

        return view('admin.fanclash.index', compact('event', 'matchups', 'rounds', 'activeRound'));
    }

    public function storeMatchup(Request $request, Event $event)
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

    public function updateMatchup(Request $request, FanClashMatchup $matchup)
    {
        $data = $this->validateMatchup($request);

        unset($data['side_a_image'], $data['side_b_image'], $data['sponsor_logo']);
        $data['is_active'] = $request->boolean('is_active', true);

        $this->applyImageUpdate($request, $matchup, $data, 'side_a_image', 'side_a_image_path');
        $this->applyImageUpdate($request, $matchup, $data, 'side_b_image', 'side_b_image_path');
        $this->applyImageUpdate($request, $matchup, $data, 'sponsor_logo', 'sponsor_logo_path');

        $matchup->update($data);

        return back()->with('success', 'Matchup updated.');
    }

    public function destroyMatchup(FanClashMatchup $matchup)
    {
        foreach (['side_a_image_path', 'side_b_image_path', 'sponsor_logo_path'] as $path) {
            if ($matchup->$path) {
                Storage::disk('public')->delete($matchup->$path);
            }
        }

        $matchup->delete();

        return back()->with('success', 'Matchup removed.');
    }

    public function startRound(Request $request, Event $event)
    {
        $data = $request->validate([
            'duration_seconds' => 'required|integer|min:5|max:120',
            'fan_clash_matchup_id' => 'nullable|integer|exists:fan_clash_matchups,id',
            'category' => 'nullable|string|max:60',
            'side_a_name' => 'required_without:fan_clash_matchup_id|nullable|string|max:60',
            'side_b_name' => 'required_without:fan_clash_matchup_id|nullable|string|max:60',
            'side_a_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'side_b_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        if ($event->activeFanClashRound()) {
            return back()->with('error', 'A round is already active. End it first.');
        }

        $snapshot = $this->resolveSnapshot($event, $data);

        if ($snapshot === null) {
            return back()->with('error', 'Pick a matchup or type both contender names.');
        }

        $round = FanClashRound::create(array_merge($snapshot, [
            'event_id' => $event->id,
            'status' => 'active',
            'duration_seconds' => $data['duration_seconds'],
            'side_a_taps' => 0,
            'side_b_taps' => 0,
            'started_at' => now(),
        ]));

        ActivityLog::record('fanclash.round_started', ['round_id' => $round->id], $event->id);

        return back()->with('success', 'Fan Clash round started!');
    }

    public function endRound(Event $event)
    {
        $round = $event->activeFanClashRound();

        if (! $round) {
            return back()->with('error', 'No active round found.');
        }

        $round->update([
            'status' => 'finished',
            'winner_side' => $round->getWinnerSide(),
            'finished_at' => now(),
        ]);

        ActivityLog::record('fanclash.round_ended', ['round_id' => $round->id], $event->id);

        return back()->with('success', 'Round ended.');
    }

    public function resetRound(FanClashRound $round)
    {
        $round->participants()->delete();
        $round->update([
            'status' => 'waiting',
            'side_a_taps' => 0,
            'side_b_taps' => 0,
            'winner_side' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);

        return back()->with('success', 'Round reset.');
    }

    public function export(Event $event)
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
     * Resolve the snapshot fields for a new round, either from a saved matchup
     * or from ad-hoc names typed at start.
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
                'side_a_color' => $matchup->side_a_color ?: ($event->primary_color ?: self::DEFAULT_SIDE_A_COLOR),
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
            'side_a_color' => ($data['side_a_color'] ?? '') ?: ($event->primary_color ?: self::DEFAULT_SIDE_A_COLOR),
            'side_b_color' => ($data['side_b_color'] ?? '') ?: self::DEFAULT_SIDE_B_COLOR,
            'side_a_image_path' => null,
            'side_b_image_path' => null,
            'sponsor_logo_path' => null,
        ];
    }

    private function storeImage(Request $request, string $field): ?string
    {
        return $request->hasFile($field)
            ? $request->file($field)->store('fanclash', 'public')
            : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyImageUpdate(Request $request, FanClashMatchup $matchup, array &$data, string $field, string $column): void
    {
        if ($request->hasFile($field)) {
            if ($matchup->$column) {
                Storage::disk('public')->delete($matchup->$column);
            }
            $data[$column] = $request->file($field)->store('fanclash', 'public');
        } elseif ($request->boolean($field.'_clear')) {
            if ($matchup->$column) {
                Storage::disk('public')->delete($matchup->$column);
            }
            $data[$column] = null;
        }
    }
}
