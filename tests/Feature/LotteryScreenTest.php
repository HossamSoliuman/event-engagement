<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\LotteryEntry;
use App\Models\LotteryScreen;
use App\Models\User;
use App\Models\VidiwallPlayer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LotteryScreenTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->event = Event::factory()->create(['module_lottery' => true]);
        Carbon::setTestNow('2026-10-13 20:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_draw_on_the_vidiwall_runs_the_intro_countdown_drawing_and_result(): void
    {
        $this->enter(5);
        $this->event->lotteryShow()->update(['countdown_seconds' => 3, 'drawing_seconds' => 4, 'result_seconds' => 10]);

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1, 'prize' => 'Helmet', 'next_rank' => 1])
            ->assertRedirect(route('admin.lottery.index', $this->event))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Revealing on the vidiwall'));

        $winner = $this->event->latestLotteryWinner();

        $feed = $this->feed()
            ->assertJsonPath('show.type', 'draw')
            ->assertJsonPath('show.phase', 'intro')
            ->assertJsonPath('show.elapsed_ms', -LotteryScreen::INTRO_MS)
            ->assertJsonPath('show.countdown_ms', 3000)
            ->assertJsonPath('show.drawing_ms', 4000)
            ->assertJsonPath('show.result_ms', 10000)
            ->assertJsonPath('show.winner.name', $winner->name)
            ->assertJsonPath('show.winner.prize', 'Helmet')
            ->assertJsonPath('show.winner.rank', 1)
            ->assertJsonPath('show.entries', 5)
            ->assertJsonCount(4, 'show.reel');
        $this->assertNotContains($winner->name, $feed->json('show.reel'));

        $this->travelToMs(1500);
        $this->feed()->assertJsonPath('show.phase', 'countdown');

        $this->travelToMs(4500);
        $this->feed()->assertJsonPath('show.phase', 'drawing');

        $this->travelToMs(8500);
        $this->feed()->assertJsonPath('show.phase', 'result');

        $this->actingAs($this->admin)
            ->getJson(route('admin.lottery.screen', $this->event))
            ->assertOk()
            ->assertJsonPath('show.phase', 'result')
            ->assertJsonPath('show.entry_id', $winner->id)
            ->assertJsonPath('show.name', $winner->name);

        $this->travelToMs(18400);
        $this->feed()->assertJsonPath('show.phase', 'result');

        $this->travelToMs(18600);
        $this->feed()->assertJsonPath('show', null);
        $this->assertNull($this->event->lotteryScreen()->first()->show_type);
    }

    public function test_a_countdown_of_zero_goes_straight_to_the_drawing(): void
    {
        $this->enter(2);
        $this->event->lotteryShow()->update(['countdown_seconds' => 0, 'drawing_seconds' => 2]);

        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1]);

        $this->travelToMs(1500);
        $this->feed()->assertJsonPath('show.phase', 'drawing')->assertJsonPath('show.countdown_ms', 0);
    }

    public function test_an_off_screen_draw_leaves_the_vidiwall_alone(): void
    {
        $this->enter(2);

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event), ['on_screen' => 0])
            ->assertSessionHas('winner_drawn');

        $this->feed()->assertJsonPath('show', null);
        $this->assertSame(1, $this->event->lotteryWinners()->count());
    }

    public function test_the_on_screen_choice_is_remembered_for_the_next_draw(): void
    {
        $this->enter(3);

        $this->actingAs($this->admin)
            ->get(route('admin.lottery.index', $this->event))
            ->assertOk()
            ->assertDontSee('name="on_screen" value="1" id="ltOnScreen" checked', false);

        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1]);
        $this->assertTrue($this->event->lotteryScreen()->first()->draw_on_screen);

        $this->actingAs($this->admin)
            ->get(route('admin.lottery.index', $this->event))
            ->assertSee('name="on_screen" value="1" id="ltOnScreen" checked', false);

        $this->travelToMs(60000);
        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event));

        $this->feed()
            ->assertJsonPath('show.type', 'draw')
            ->assertJsonPath('show.winner.rank', 2);
    }

    public function test_reveal_now_skips_to_the_result(): void
    {
        $this->enter(3);
        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.lottery.screen.reveal', $this->event))
            ->assertOk()
            ->assertJsonPath('show.phase', 'result');

        $this->feed()->assertJsonPath('show.phase', 'result');
    }

    public function test_nothing_else_takes_the_screen_while_a_draw_is_revealing(): void
    {
        $this->enter(4);
        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1]);
        $first = $this->event->latestLotteryWinner();

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1, 'prize' => 'Scarf'])
            ->assertSessionHas('error', 'A draw is still running on the vidiwall. Reveal it or hide it first.')
            ->assertSessionHasInput('prize', 'Scarf');
        $this->assertSame(1, $this->event->lotteryWinners()->count());

        $this->actingAs($this->admin)
            ->postJson(route('admin.lottery.screen.board', $this->event))
            ->assertStatus(409);

        $this->actingAs($this->admin)
            ->postJson(route('admin.lottery.screen.winner', [$this->event, $first]))
            ->assertStatus(409);

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event), ['on_screen' => 0])
            ->assertSessionHas('winner_drawn');
        $this->assertSame(2, $this->event->lotteryWinners()->count());
        $this->feed()->assertJsonPath('show.winner.name', $first->name);

        $this->travelToMs(30000);
        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1])
            ->assertSessionHas('winner_drawn');
        $this->feed()->assertJsonPath('show.winner.rank', 3);
    }

    public function test_hiding_the_lottery_resumes_the_playlist_it_paused(): void
    {
        $this->enter(2);
        $this->upload();
        $this->upload();
        $this->event->player()->play();

        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1]);
        $this->assertSame(VidiwallPlayer::STATE_PAUSED, $this->event->vidiwallPlayer()->first()->state);

        $this->actingAs($this->admin)
            ->postJson(route('admin.lottery.screen.hide', $this->event))
            ->assertOk()
            ->assertJsonPath('show', null)
            ->assertJsonPath('message', 'The lottery is off the vidiwall.');

        $this->feed()->assertJsonPath('show', null);
        $this->assertSame(VidiwallPlayer::STATE_PLAYING, $this->event->vidiwallPlayer()->first()->state);
    }

    public function test_a_finished_show_ends_by_itself_and_resumes_the_playlist(): void
    {
        $this->enter(2);
        $this->upload();
        $this->event->player()->play();
        $this->event->lotteryShow()->update(['countdown_seconds' => 1, 'drawing_seconds' => 1, 'result_seconds' => 5]);

        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1]);

        $this->travelToMs(9000);
        $this->feed()->assertJsonPath('show', null);
        $this->assertSame(VidiwallPlayer::STATE_PLAYING, $this->event->vidiwallPlayer()->first()->state);
    }

    public function test_a_playlist_paused_by_the_operator_stays_paused_after_the_show(): void
    {
        $this->enter(2);
        $this->upload();
        $player = $this->event->player();
        $player->play();
        $player->pause();

        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1]);
        $this->actingAs($this->admin)->postJson(route('admin.lottery.screen.hide', $this->event))->assertOk();

        $this->assertSame(VidiwallPlayer::STATE_PAUSED, $this->event->vidiwallPlayer()->first()->state);
    }

    public function test_a_winner_and_the_winners_board_can_be_put_on_screen(): void
    {
        $this->enter(3);
        $first = $this->event->drawLotteryWinner('Grand prize');
        $second = $this->event->drawLotteryWinner('Scarf');

        $this->actingAs($this->admin)
            ->postJson(route('admin.lottery.screen.winner', [$this->event, $first]))
            ->assertOk()
            ->assertJsonPath('show.type', 'winner')
            ->assertJsonPath('message', "{$first->name} is on the vidiwall.");

        $this->feed()
            ->assertJsonPath('show.type', 'winner')
            ->assertJsonPath('show.phase', 'result')
            ->assertJsonPath('show.winner.name', $first->name)
            ->assertJsonPath('show.winner.prize', 'Grand prize')
            ->assertJsonPath('show.reel', []);

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.screen.board', $this->event))
            ->assertRedirect(route('admin.lottery.index', $this->event))
            ->assertSessionHas('success', 'The winners are on the vidiwall.');

        $this->travelToMs(600000);
        $this->feed()
            ->assertJsonPath('show.type', 'board')
            ->assertJsonPath('show.phase', 'result')
            ->assertJsonPath('show.result_ms', 0)
            ->assertJsonPath('show.winners.0.name', $first->name)
            ->assertJsonPath('show.winners.1.name', $second->name)
            ->assertJsonPath('show.winners.1.rank', 2)
            ->assertJsonPath('show.winners.1.prize', 'Scarf');
    }

    public function test_the_board_needs_a_winner_and_only_winners_go_on_screen(): void
    {
        $entry = LotteryEntry::factory()->for($this->event)->create();

        $this->actingAs($this->admin)
            ->postJson(route('admin.lottery.screen.board', $this->event))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Draw a winner before showing the winners.');

        $this->actingAs($this->admin)
            ->postJson(route('admin.lottery.screen.winner', [$this->event, $entry]))
            ->assertStatus(422);

        $this->feed()->assertJsonPath('show', null);
    }

    public function test_putting_back_the_winner_on_screen_takes_the_show_off(): void
    {
        $this->enter(3);
        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1]);
        $winner = $this->event->latestLotteryWinner();

        $this->actingAs($this->admin)->post(route('admin.lottery.release', [$this->event, $winner]));

        $this->feed()->assertJsonPath('show', null);
    }

    public function test_reset_takes_the_lottery_off_the_screen(): void
    {
        $this->enter(3);
        $this->event->drawLotteryWinner();
        $this->actingAs($this->admin)->post(route('admin.lottery.screen.board', $this->event));

        $this->actingAs($this->admin)->post(route('admin.lottery.reset', $this->event));

        $this->feed()->assertJsonPath('show', null);
    }

    public function test_redraw_on_screen_runs_a_new_draw_for_the_same_place(): void
    {
        $this->enter(3);
        $first = $this->event->drawLotteryWinner('Grand prize');

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.redraw', [$this->event, $first]), ['on_screen' => 1])
            ->assertSessionHas('winner_drawn');

        $replacement = $this->event->latestLotteryWinner();
        $this->assertNotSame($first->id, $replacement->id);

        $this->feed()
            ->assertJsonPath('show.type', 'draw')
            ->assertJsonPath('show.winner.name', $replacement->name)
            ->assertJsonPath('show.winner.rank', 1)
            ->assertJsonPath('show.winner.prize', 'Grand prize');
    }

    public function test_the_screen_shows_names_the_way_the_settings_say(): void
    {
        $winner = LotteryEntry::factory()->for($this->event)->winner(1, 'Helmet')->create([
            'name' => 'Anna  Maria Keller',
            'phone' => '+49 170 1234567',
        ]);
        $this->event->lotteryShow()->update(['name_format' => 'short', 'show_phone' => true, 'show_prize' => false]);

        $this->actingAs($this->admin)->postJson(route('admin.lottery.screen.winner', [$this->event, $winner]))->assertOk();

        $this->feed()
            ->assertJsonPath('show.winner.name', 'Anna K.')
            ->assertJsonPath('show.winner.detail', '••• 567')
            ->assertJsonPath('show.winner.prize', null)
            ->assertJsonPath('show.headline', $this->event->fresh()->lottery_title);

        $this->event->update(['lottery_title' => 'Gewinnspiel']);
        $this->feed()->assertJsonPath('show.headline', 'Gewinnspiel');

        $this->event->lotteryShow()->update(['headline' => 'Prize Draw']);
        $this->feed()->assertJsonPath('show.headline', 'Prize Draw');
    }

    public function test_show_settings_are_saved(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.lottery.screen.update', $this->event), [
                'countdown_seconds' => 10,
                'drawing_seconds' => 8,
                'hold_result' => 1,
                'headline' => '  Big Draw ',
                'name_format' => 'short',
                'show_prize' => 0,
                'show_phone' => 1,
                'show_entry_count' => 0,
                'confetti' => 1,
            ])
            ->assertRedirect(route('admin.lottery.index', $this->event).'#vidiwall-show')
            ->assertSessionHas('success', 'Vidiwall show settings saved.');

        $screen = $this->event->lotteryScreen()->first();
        $this->assertSame(10, $screen->countdown_seconds);
        $this->assertSame(8, $screen->drawing_seconds);
        $this->assertSame(0, $screen->result_seconds);
        $this->assertSame('Big Draw', $screen->headline);
        $this->assertSame('short', $screen->name_format);
        $this->assertFalse($screen->show_prize);
        $this->assertTrue($screen->show_phone);
        $this->assertFalse($screen->show_entry_count);
        $this->assertTrue($screen->confetti);

        $this->actingAs($this->admin)
            ->put(route('admin.lottery.screen.update', $this->event), [
                'countdown_seconds' => 0,
                'drawing_seconds' => 1,
                'hold_result' => 0,
                'result_seconds' => 45,
                'headline' => '',
                'name_format' => 'full',
            ])
            ->assertSessionHasNoErrors();

        $screen->refresh();
        $this->assertSame(0, $screen->countdown_seconds);
        $this->assertSame(45, $screen->result_seconds);
        $this->assertNull($screen->headline);
        $this->assertFalse($screen->confetti);
    }

    public function test_show_settings_are_validated(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.lottery.screen.update', $this->event), [
                'countdown_seconds' => 61,
                'drawing_seconds' => 0,
                'hold_result' => 0,
                'result_seconds' => 2,
                'name_format' => 'initials',
                'headline' => str_repeat('x', 61),
            ])
            ->assertSessionHasErrors([
                'countdown_seconds' => 'The countdown can run for 60 seconds at most.',
                'drawing_seconds' => 'The drawing needs at least 1 second.',
                'result_seconds' => 'Show the winner for at least 3 seconds.',
                'name_format' => 'Choose how winner names show on screen.',
                'headline' => 'Keep the headline to 60 characters.',
            ]);

        $this->actingAs($this->admin)
            ->put(route('admin.lottery.screen.update', $this->event), [
                'countdown_seconds' => 5,
                'drawing_seconds' => 5,
                'name_format' => 'full',
            ])
            ->assertSessionHasErrors(['result_seconds']);
    }

    public function test_moderators_run_the_show_for_their_own_event_only(): void
    {
        $this->enter(2);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->get(route('moderator.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Vidiwall Show')
            ->assertSee(json_encode(route('moderator.lottery.screen', $this->event)), false);

        $this->actingAs($moderator)
            ->post(route('moderator.lottery.draw', $this->event), ['on_screen' => 1])
            ->assertRedirect(route('moderator.lottery.index', $this->event));

        $this->actingAs($moderator)
            ->postJson(route('moderator.lottery.screen.reveal', $this->event))
            ->assertOk()
            ->assertJsonPath('show.phase', 'result');

        $this->actingAs($moderator)
            ->put(route('moderator.lottery.screen.update', $this->event), ['countdown_seconds' => 4, 'drawing_seconds' => 4, 'hold_result' => 1, 'name_format' => 'full'])
            ->assertRedirect(route('moderator.lottery.index', $this->event).'#vidiwall-show');

        $other = Event::factory()->create(['module_lottery' => true]);

        $this->actingAs($moderator)
            ->postJson(route('moderator.lottery.screen.board', $other))
            ->assertForbidden();
    }

    public function test_guests_cannot_control_the_show(): void
    {
        $this->post(route('admin.lottery.screen.board', $this->event))->assertRedirect();
        $this->post(route('admin.lottery.screen.hide', $this->event))->assertRedirect();

        $this->assertNull($this->event->lotteryScreen()->first());
    }

    public function test_the_feed_is_quiet_until_the_lottery_has_used_the_screen(): void
    {
        $this->getJson(route('vidiwall.lottery.feed', $this->event->slug))
            ->assertOk()
            ->assertExactJson(['revision' => 0, 'show' => null]);
    }

    public function test_the_vidiwall_carries_the_lottery_overlay_when_the_module_is_on(): void
    {
        $this->get(route('vidiwall.show', $this->event->slug))
            ->assertOk()
            ->assertSee('id="lotOverlay"', false)
            ->assertSee('const HAS_LOTTERY = true;', false);

        $this->event->update(['module_lottery' => false]);

        $this->get(route('vidiwall.show', $this->event->slug))
            ->assertOk()
            ->assertDontSee('id="lotOverlay"', false);
    }

    private function feed(): TestResponse
    {
        return $this->getJson(route('vidiwall.lottery.feed', $this->event->slug))->assertOk();
    }

    private function travelToMs(int $milliseconds): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 20:00:00')->addMilliseconds($milliseconds));
    }

    private function enter(int $count): void
    {
        foreach (range(1, $count) as $i) {
            LotteryEntry::factory()->for($this->event)->create(['name' => "Guest {$i}"]);
        }
    }

    private function upload(): FotoUpload
    {
        return FotoUpload::query()->create([
            'event_id' => $this->event->id,
            'file_path' => 'fotos/event-'.$this->event->id.'/'.uniqid('upload-', true).'.jpg',
            'media_type' => 'photo',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploader_name' => 'Guest',
            'status' => 'approved',
            'display_order' => (int) FotoUpload::query()->where('event_id', $this->event->id)->where('status', 'approved')->max('display_order') + 1,
            'approved_at' => now(),
        ]);
    }
}
