<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FanClashMatchup;
use App\Models\FanClashRound;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FanClashTest extends TestCase
{
    use DatabaseTransactions;

    /** Mirrors FanClashController::CLAMP_GRACE_SECONDS. */
    private const CLAMP_GRACE = 2;

    private function event(array $overrides = []): Event
    {
        return Event::factory()->create(array_merge([
            'module_fanclash' => true,
            'primary_color' => '#FF3D00',
        ], $overrides));
    }

    private function activeRound(Event $event, array $overrides = []): FanClashRound
    {
        return FanClashRound::create(array_merge([
            'event_id' => $event->id,
            'category' => 'Downhill',
            'side_a_name' => 'Odermatt',
            'side_b_name' => 'Kilde',
            'side_a_color' => '#FF3D00',
            'side_b_color' => '#3B82F6',
            'status' => 'active',
            'duration_seconds' => 20,
            'side_a_taps' => 0,
            'side_b_taps' => 0,
            'started_at' => now(),
        ], $overrides));
    }

    public function test_tap_batch_increments_the_correct_counter(): void
    {
        $event = $this->event();
        $round = $this->activeRound($event);

        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'sess-a',
            'side' => 'a',
            'taps' => 5,
        ])->assertOk()->assertJson(['side_a_taps' => 5, 'side_b_taps' => 0]);

        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'sess-b',
            'side' => 'b',
            'taps' => 3,
        ])->assertOk()->assertJson(['side_a_taps' => 5, 'side_b_taps' => 3]);

        $round->refresh();
        $this->assertSame(5, $round->side_a_taps);
        $this->assertSame(3, $round->side_b_taps);
    }

    public function test_batch_clamping_scales_with_elapsed_time(): void
    {
        $event = $this->event();

        // Only ~2s into a 20s round: the ceiling is far below the full-round max
        // of 500, so a scripted phone cannot dump the whole round in one batch.
        $round = $this->activeRound($event, [
            'started_at' => now()->subSeconds(2),
            'duration_seconds' => 20,
        ]);

        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'cheater',
            'side' => 'a',
            'taps' => 100000,
        ])->assertOk();

        $round->refresh();
        // Ceiling ≈ 25 * (elapsed + grace); comfortably under the 500 full-round cap.
        $this->assertGreaterThan(0, $round->side_a_taps);
        $this->assertLessThanOrEqual(25 * (2 + self::CLAMP_GRACE + 1), $round->side_a_taps);
        $this->assertLessThan(500, $round->side_a_taps);
    }

    public function test_batch_clamping_never_exceeds_full_round_ceiling(): void
    {
        $event = $this->event();

        // Near the end of a 20s round the ceiling saturates at 25 * 20 = 500.
        $round = $this->activeRound($event, [
            'started_at' => now()->subSeconds(19),
            'duration_seconds' => 20,
        ]);

        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'cheater',
            'side' => 'a',
            'taps' => 100000,
        ])->assertOk()->assertJson(['side_a_taps' => 500]);

        // A second implausible batch cannot push the counter past the ceiling.
        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'cheater',
            'side' => 'a',
            'taps' => 100000,
        ])->assertOk();

        $round->refresh();
        $this->assertSame(500, $round->side_a_taps);
    }

    public function test_taps_after_expiry_are_rejected(): void
    {
        $event = $this->event();
        $round = $this->activeRound($event, [
            'started_at' => now()->subSeconds(25),
            'duration_seconds' => 20,
        ]);

        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'late',
            'side' => 'a',
            'taps' => 4,
        ])->assertStatus(422);

        $round->refresh();
        $this->assertSame('finished', $round->status);
        $this->assertSame(0, $round->side_a_taps);
        $this->assertDatabaseCount('fan_clash_participants', 0);
    }

    public function test_one_participant_row_per_session(): void
    {
        $event = $this->event();
        $round = $this->activeRound($event);

        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'same',
            'side' => 'a',
            'taps' => 3,
        ])->assertOk();

        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'same',
            'side' => 'a',
            'taps' => 4,
        ])->assertOk()->assertJson(['your_taps' => 7]);

        $this->assertDatabaseCount('fan_clash_participants', 1);
        $round->refresh();
        $this->assertSame(7, $round->side_a_taps);
    }

    public function test_participant_side_stays_fixed_after_first_pick(): void
    {
        $event = $this->event();
        $round = $this->activeRound($event);

        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'switcher',
            'side' => 'a',
            'taps' => 2,
        ])->assertOk();

        // Even if the client posts 'b', the stored side wins.
        $this->postJson("/e/{$event->slug}/clash/tap", [
            'session_token' => 'switcher',
            'side' => 'b',
            'taps' => 5,
        ])->assertOk()->assertJson(['side' => 'a']);

        $round->refresh();
        $this->assertSame(7, $round->side_a_taps);
        $this->assertSame(0, $round->side_b_taps);
    }

    public function test_winner_and_tie_are_computed_from_counters(): void
    {
        $a = new FanClashRound(['side_a_taps' => 10, 'side_b_taps' => 4]);
        $this->assertSame('a', $a->getWinnerSide());

        $b = new FanClashRound(['side_a_taps' => 4, 'side_b_taps' => 10]);
        $this->assertSame('b', $b->getWinnerSide());

        $tie = new FanClashRound(['side_a_taps' => 7, 'side_b_taps' => 7]);
        $this->assertSame('tie', $tie->getWinnerSide());
    }

    public function test_status_transitions_waiting_active_finished(): void
    {
        $event = $this->event();

        // Waiting: no rounds yet.
        $this->getJson("/e/{$event->slug}/clash/status")
            ->assertOk()->assertJson(['status' => 'waiting']);

        // Active.
        $round = $this->activeRound($event, ['side_a_taps' => 9, 'side_b_taps' => 2]);
        $this->getJson("/e/{$event->slug}/clash/status")
            ->assertOk()->assertJson(['status' => 'active', 'round_id' => $round->id]);

        // Reading an expired active round lazily finalizes it.
        $round->update(['started_at' => now()->subSeconds(30)]);
        $this->getJson("/e/{$event->slug}/clash/status")
            ->assertOk()->assertJson(['status' => 'finished', 'winner_side' => 'a']);

        $round->refresh();
        $this->assertSame('finished', $round->status);
        $this->assertSame('a', $round->winner_side);
    }

    public function test_clash_feed_returns_expected_shape(): void
    {
        $event = $this->event();
        $round = $this->activeRound($event, ['side_a_taps' => 6, 'side_b_taps' => 4]);

        $this->getJson("/screen/{$event->slug}/clash/feed")
            ->assertOk()
            ->assertJson([
                'status' => 'active',
                'round_id' => $round->id,
                'side_a_name' => 'Odermatt',
                'side_b_name' => 'Kilde',
                'side_a_taps' => 6,
                'side_b_taps' => 4,
            ])
            ->assertJsonStructure([
                'status', 'round_id', 'category',
                'side_a_name', 'side_b_name', 'side_a_color', 'side_b_color',
                'side_a_taps', 'side_b_taps', 'headcount_a', 'headcount_b',
                'remaining_ms', 'winner_side', 'sponsor_logo_url', 'event_name',
            ]);
    }

    public function test_clash_feed_is_idle_without_a_round(): void
    {
        $event = $this->event();

        $this->getJson("/screen/{$event->slug}/clash/feed")
            ->assertOk()->assertExactJson(['status' => 'idle']);
    }

    public function test_admin_can_start_end_and_reset_a_round(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event();

        // Start an ad-hoc round.
        $this->actingAs($admin)
            ->post(route('admin.fanclash.start', $event), [
                'duration_seconds' => 20,
                'side_a_name' => 'Odermatt',
                'side_b_name' => 'Kilde',
                'side_a_color' => '#FF3D00',
                'side_b_color' => '#3B82F6',
            ])->assertRedirect();

        $round = $event->activeFanClashRound();
        $this->assertNotNull($round);
        $this->assertSame('active', $round->status);
        $this->assertSame('Odermatt', $round->side_a_name);

        $round->update(['side_a_taps' => 8, 'side_b_taps' => 3]);

        // End it — winner comes from the counters.
        $this->actingAs($admin)
            ->post(route('admin.fanclash.end', $event))
            ->assertRedirect();

        $round->refresh();
        $this->assertSame('finished', $round->status);
        $this->assertSame('a', $round->winner_side);

        // Reset clears the counters and participants.
        $this->actingAs($admin)
            ->post(route('admin.fanclash.rounds.reset', $round))
            ->assertRedirect();

        $round->refresh();
        $this->assertSame('waiting', $round->status);
        $this->assertSame(0, $round->side_a_taps);
        $this->assertNull($round->winner_side);
        $this->assertNull($round->started_at);
    }

    public function test_admin_cannot_start_two_active_rounds(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event();
        $this->activeRound($event);

        $this->actingAs($admin)
            ->post(route('admin.fanclash.start', $event), [
                'duration_seconds' => 20,
                'side_a_name' => 'A',
                'side_b_name' => 'B',
            ])->assertRedirect();

        $this->assertSame(1, $event->fanClashRounds()->where('status', 'active')->count());
    }

    public function test_starting_from_a_matchup_snapshots_its_names_and_colors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event();

        $matchup = FanClashMatchup::create([
            'event_id' => $event->id,
            'category' => 'Large Hill',
            'side_a_name' => 'Kraft',
            'side_b_name' => 'Kobayashi',
            'side_a_color' => '#111111',
            'side_b_color' => '#222222',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.fanclash.start', $event), [
                'duration_seconds' => 30,
                'fan_clash_matchup_id' => $matchup->id,
            ])->assertRedirect();

        $round = $event->activeFanClashRound();
        $this->assertSame('Kraft', $round->side_a_name);
        $this->assertSame('Kobayashi', $round->side_b_name);
        $this->assertSame('#111111', $round->side_a_color);
        $this->assertSame('Large Hill', $round->category);
        $this->assertSame($matchup->id, $round->fan_clash_matchup_id);
    }

    public function test_start_round_rejects_a_malformed_color(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event();

        $this->actingAs($admin)
            ->post(route('admin.fanclash.start', $event), [
                'duration_seconds' => 20,
                'side_a_name' => 'Red',
                'side_b_name' => 'Blue',
                'side_a_color' => 'red;evil',
            ])->assertSessionHasErrors('side_a_color');

        $this->assertNull($event->activeFanClashRound());
    }

    public function test_moderator_can_start_a_round_for_their_event(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event = $this->event();
        $event->moderators()->attach($moderator->id);

        $this->actingAs($moderator)
            ->post(route('moderator.fanclash.start', $event), [
                'duration_seconds' => 15,
                'side_a_name' => 'Red',
                'side_b_name' => 'Blue',
            ])->assertRedirect();

        $round = $event->activeFanClashRound();
        $this->assertNotNull($round);
        $this->assertSame(15, $round->duration_seconds);
    }
}
