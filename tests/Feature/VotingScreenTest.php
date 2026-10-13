<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\LotteryEntry;
use App\Models\User;
use App\Models\VidiwallPlayer;
use App\Models\Vote;
use App\Models\VotingScreen;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VotingScreenTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->event = Event::factory()->create([
            'module_voting' => true,
            'module_lottery' => true,
            'voting_title' => 'Athlete of the Day',
            'voting_options' => [
                ['name' => 'Anna Keller', 'slug' => 'anna-keller', 'position' => 'Downhill', 'image' => null],
                ['name' => 'Ben Otto', 'slug' => 'ben-otto', 'position' => 'Slalom', 'image' => null],
                ['name' => 'Cara Lind', 'slug' => 'cara-lind', 'position' => null, 'image' => null],
            ],
        ]);
        Carbon::setTestNow('2026-10-13 20:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_standings_list_every_athlete_best_first_with_shared_ranks(): void
    {
        $this->vote('Ben Otto', 3);
        $this->vote('Anna Keller', 1);
        $this->vote('Old Name', 1);

        $standings = $this->event->voteStandings();

        $this->assertSame(['Ben Otto', 'Anna Keller', 'Old Name', 'Cara Lind'], array_column($standings, 'name'));
        $this->assertSame([1, 2, 2, 4], array_column($standings, 'rank'));
        $this->assertSame([3, 1, 1, 0], array_column($standings, 'votes'));
        $this->assertSame([60, 20, 20, 0], array_column($standings, 'percent'));
        $this->assertSame(['Slalom', 'Downhill', null, null], array_column($standings, 'position'));
        $this->assertCount(4, array_unique(array_column($standings, 'key')));
    }

    public function test_live_results_go_up_and_follow_the_votes(): void
    {
        $this->vote('Anna Keller', 2);
        $this->vote('Ben Otto', 1);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.live', $this->event))
            ->assertOk()
            ->assertJsonPath('show.type', 'live')
            ->assertJsonPath('message', 'The live results are on the vidiwall.');

        $revision = $this->feed()
            ->assertJsonPath('show.type', 'live')
            ->assertJsonPath('show.phase', 'result')
            ->assertJsonPath('show.result_ms', 0)
            ->assertJsonPath('show.headline', 'Athlete of the Day')
            ->assertJsonPath('show.open', true)
            ->assertJsonPath('show.qr', true)
            ->assertJsonPath('show.total', 3)
            ->assertJsonCount(3, 'show.standings')
            ->assertJsonPath('show.standings.0.name', 'Anna Keller')
            ->assertJsonPath('show.standings.0.position', 'Downhill')
            ->assertJsonPath('show.standings.0.votes', 2)
            ->assertJsonPath('show.standings.0.percent', 67)
            ->assertJsonPath('show.standings.0.share', 1)
            ->assertJsonPath('show.standings.1.share', 0.5)
            ->assertJsonPath('show.standings.2.name', 'Cara Lind')
            ->assertJsonPath('show.standings.2.votes', 0)
            ->assertJsonPath('show.winners', [])
            ->json('revision');

        $this->vote('Ben Otto', 2);
        $this->travelToMs(600000);

        $this->feed()
            ->assertJsonPath('revision', $revision)
            ->assertJsonPath('show.type', 'live')
            ->assertJsonPath('show.total', 5)
            ->assertJsonPath('show.standings.0.name', 'Ben Otto')
            ->assertJsonPath('show.standings.0.votes', 3);
    }

    public function test_live_results_stop_at_the_place_the_settings_say_ties_included(): void
    {
        $this->vote('Anna Keller', 3);
        $this->vote('Ben Otto', 1);
        $this->vote('Cara Lind', 1);
        $this->event->votingShow()->update(['top_count' => 2]);

        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.live', $this->event))->assertOk();
        $this->feed()->assertJsonCount(3, 'show.standings');

        $this->event->votingShow()->update(['top_count' => 1]);
        $this->feed()
            ->assertJsonCount(1, 'show.standings')
            ->assertJsonPath('show.standings.0.name', 'Anna Keller');
    }

    public function test_the_screen_shows_only_what_the_settings_say(): void
    {
        $this->vote('Anna Keller', 2);
        $this->event->votingShow()->update([
            'headline' => 'Fan MVP',
            'show_votes' => false,
            'show_percent' => false,
            'show_position' => false,
            'show_total' => false,
            'show_qr' => false,
        ]);

        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.live', $this->event))->assertOk();

        $this->feed()
            ->assertJsonPath('show.headline', 'Fan MVP')
            ->assertJsonPath('show.qr', false)
            ->assertJsonPath('show.total', null)
            ->assertJsonPath('show.standings.0.votes', null)
            ->assertJsonPath('show.standings.0.percent', null)
            ->assertJsonPath('show.standings.0.position', null)
            ->assertJsonPath('show.standings.0.share', 1);
    }

    public function test_the_qr_code_goes_once_voting_closes(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.live', $this->event))->assertOk();
        $this->feed()->assertJsonPath('show.qr', true);

        $this->actingAs($this->admin)->post(route('admin.voting.close', $this->event));

        $this->feed()
            ->assertJsonPath('show.type', 'live')
            ->assertJsonPath('show.open', false)
            ->assertJsonPath('show.qr', false);
    }

    public function test_a_reveal_counts_down_to_the_winner_and_closes_voting_first(): void
    {
        $this->vote('Ben Otto', 4);
        $this->vote('Anna Keller', 2);
        $this->event->votingShow()->update(['countdown_seconds' => 3, 'result_seconds' => 10]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.reveal', $this->event))
            ->assertOk()
            ->assertJsonPath('show.type', 'reveal')
            ->assertJsonPath('show.phase', 'intro')
            ->assertJsonPath('show.winners', ['Ben Otto'])
            ->assertJsonPath('voting_closed', true)
            ->assertJsonPath('message', 'Revealing Ben Otto on the vidiwall.');

        $this->assertTrue($this->event->fresh()->voting_closed);
        $this->assertTrue(ActivityLog::query()->where('event_id', $this->event->id)->where('action', 'voting.revealed')->exists());

        $this->feed()
            ->assertJsonPath('show.phase', 'intro')
            ->assertJsonPath('show.elapsed_ms', -VotingScreen::INTRO_MS)
            ->assertJsonPath('show.countdown_ms', 3000)
            ->assertJsonPath('show.result_ms', 10000)
            ->assertJsonPath('show.standings', [])
            ->assertJsonPath('show.total', 6)
            ->assertJsonPath('show.winners.0.name', 'Ben Otto')
            ->assertJsonPath('show.winners.0.position', 'Slalom')
            ->assertJsonPath('show.winners.0.votes', 4)
            ->assertJsonPath('show.winners.0.percent', 67);

        $this->travelToMs(1500);
        $this->feed()->assertJsonPath('show.phase', 'countdown');

        $this->vote('Anna Keller', 5);

        $this->travelToMs(4500);
        $this->feed()
            ->assertJsonPath('show.phase', 'result')
            ->assertJsonPath('show.winners.0.name', 'Ben Otto')
            ->assertJsonPath('show.total', 6);

        $this->travelToMs(14400);
        $this->feed()->assertJsonPath('show.phase', 'result');

        $this->travelToMs(14600);
        $this->feed()->assertJsonPath('show', null);
        $this->assertNull($this->event->votingScreen()->first()->show_type);
    }

    public function test_a_reveal_can_leave_voting_open(): void
    {
        $this->vote('Anna Keller', 1);
        $this->event->votingShow()->update(['close_on_reveal' => false]);

        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.reveal', $this->event))->assertOk();

        $this->assertFalse($this->event->fresh()->voting_closed);
    }

    public function test_a_tie_reveals_everyone_sharing_first_place(): void
    {
        $this->vote('Anna Keller', 2);
        $this->vote('Cara Lind', 2);
        $this->vote('Ben Otto', 1);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.reveal', $this->event))
            ->assertOk()
            ->assertJsonPath('message', 'Revealing a tie on the vidiwall: Anna Keller and Cara Lind.');

        $this->feed()
            ->assertJsonCount(2, 'show.winners')
            ->assertJsonPath('show.winners.0.name', 'Anna Keller')
            ->assertJsonPath('show.winners.1.name', 'Cara Lind');
    }

    public function test_shows_that_need_votes_or_athletes_say_so(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.reveal', $this->event))
            ->assertStatus(422)
            ->assertJsonPath('message', 'No votes yet, so there is no winner to reveal.');

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.podium', $this->event))
            ->assertStatus(422)
            ->assertJsonPath('message', 'There is no podium until the first votes are in.');

        $this->assertFalse($this->event->fresh()->voting_closed);
        $this->feed()->assertJsonPath('show', null);

        $this->event->update(['voting_options' => []]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.live', $this->event))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Add athletes to the vote in the event settings first.');
    }

    public function test_reveal_now_skips_the_countdown(): void
    {
        $this->vote('Anna Keller', 1);
        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.reveal', $this->event))->assertOk();

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.reveal-now', $this->event))
            ->assertOk()
            ->assertJsonPath('show.phase', 'result');

        $this->feed()->assertJsonPath('show.phase', 'result');
    }

    public function test_nothing_else_takes_the_screen_while_the_winner_is_on_the_way(): void
    {
        $this->vote('Anna Keller', 1);
        LotteryEntry::factory()->for($this->event)->count(2)->create();
        $winner = $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.reveal', $this->event))->assertOk();

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.live', $this->event))
            ->assertStatus(409)
            ->assertJsonPath('message', 'The winner is still being revealed on the vidiwall. Reveal it now or hide it first.');

        $this->actingAs($this->admin)
            ->postJson(route('admin.lottery.screen.winner', [$this->event, $winner]))
            ->assertStatus(409)
            ->assertJsonPath('message', 'The vote winner is still being revealed on the vidiwall. Wait for it or hide it first.');

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1])
            ->assertSessionHas('error', 'The vote winner is still being revealed on the vidiwall. Wait for it or hide it first.');

        $this->feed()->assertJsonPath('show.type', 'reveal');

        $this->travelToMs(60000);
        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.podium', $this->event))->assertOk();
    }

    public function test_a_lottery_draw_holds_the_screen_until_its_winner_is_out(): void
    {
        $this->vote('Anna Keller', 1);
        LotteryEntry::factory()->for($this->event)->count(2)->create();
        $this->actingAs($this->admin)->post(route('admin.lottery.draw', $this->event), ['on_screen' => 1]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.voting.screen', $this->event))
            ->assertOk()
            ->assertJsonPath('blocked', 'A lottery draw is still running on the vidiwall. Wait for its winner or hide it first.');

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.live', $this->event))
            ->assertStatus(409);

        $this->feed()->assertJsonPath('show', null);
    }

    public function test_only_one_show_is_on_the_screen_at_a_time(): void
    {
        $this->vote('Anna Keller', 1);
        LotteryEntry::factory()->for($this->event)->count(2)->create();
        $winner = $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)->postJson(route('admin.lottery.screen.winner', [$this->event, $winner]))->assertOk();
        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.live', $this->event))->assertOk();

        $this->lotteryFeed()->assertJsonPath('show', null);
        $this->feed()->assertJsonPath('show.type', 'live');

        $this->actingAs($this->admin)->postJson(route('admin.lottery.screen.board', $this->event))->assertOk();

        $this->feed()->assertJsonPath('show', null);
        $this->lotteryFeed()->assertJsonPath('show.type', 'board');
    }

    public function test_the_playlist_pauses_for_the_vote_and_carries_on_when_it_is_hidden(): void
    {
        $this->upload();
        $this->upload();
        $this->event->player()->play();

        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.live', $this->event))->assertOk();
        $this->assertSame(VidiwallPlayer::STATE_PAUSED, $this->event->vidiwallPlayer()->first()->state);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.hide', $this->event))
            ->assertOk()
            ->assertJsonPath('show', null)
            ->assertJsonPath('message', 'The vote is off the vidiwall.');

        $this->feed()->assertJsonPath('show', null);
        $this->assertSame(VidiwallPlayer::STATE_PLAYING, $this->event->vidiwallPlayer()->first()->state);
    }

    public function test_a_playlist_handed_from_the_lottery_to_the_vote_carries_on_after_the_vote(): void
    {
        $this->upload();
        $this->event->player()->play();
        LotteryEntry::factory()->for($this->event)->count(2)->create();
        $winner = $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)->postJson(route('admin.lottery.screen.winner', [$this->event, $winner]))->assertOk();
        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.live', $this->event))->assertOk();
        $this->assertSame(VidiwallPlayer::STATE_PAUSED, $this->event->vidiwallPlayer()->first()->state);
        $this->assertFalse($this->event->lotteryScreen()->first()->resume_player);

        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.hide', $this->event))->assertOk();

        $this->assertSame(VidiwallPlayer::STATE_PLAYING, $this->event->vidiwallPlayer()->first()->state);
    }

    public function test_a_playlist_paused_by_the_operator_stays_paused_after_the_vote(): void
    {
        $this->upload();
        $player = $this->event->player();
        $player->play();
        $player->pause();

        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.live', $this->event))->assertOk();
        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.hide', $this->event))->assertOk();

        $this->assertSame(VidiwallPlayer::STATE_PAUSED, $this->event->vidiwallPlayer()->first()->state);
    }

    public function test_a_timed_winner_ends_by_itself_and_the_playlist_carries_on(): void
    {
        $this->upload();
        $this->event->player()->play();
        $this->vote('Anna Keller', 1);
        $this->event->votingShow()->update(['countdown_seconds' => 1, 'result_seconds' => 5]);

        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.reveal', $this->event))->assertOk();

        $this->travelToMs(8000);
        $this->feed()->assertJsonPath('show', null);
        $this->assertSame(VidiwallPlayer::STATE_PLAYING, $this->event->vidiwallPlayer()->first()->state);
    }

    public function test_the_podium_shows_the_top_three_places_with_votes(): void
    {
        $this->vote('Cara Lind', 3);
        $this->vote('Anna Keller', 1);

        $this->actingAs($this->admin)
            ->post(route('admin.voting.screen.podium', $this->event))
            ->assertRedirect(route('admin.voting.index', $this->event))
            ->assertSessionHas('success', 'The podium is on the vidiwall.');

        $this->travelToMs(600000);
        $this->feed()
            ->assertJsonPath('show.type', 'podium')
            ->assertJsonPath('show.phase', 'result')
            ->assertJsonPath('show.qr', false)
            ->assertJsonCount(2, 'show.standings')
            ->assertJsonPath('show.standings.0.name', 'Cara Lind')
            ->assertJsonPath('show.standings.0.rank', 1)
            ->assertJsonPath('show.standings.1.name', 'Anna Keller')
            ->assertJsonPath('show.standings.1.rank', 2);
    }

    public function test_resetting_the_votes_takes_the_vote_off_the_screen(): void
    {
        $this->vote('Anna Keller', 2);
        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.live', $this->event))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.voting.reset', $this->event));

        $this->feed()->assertJsonPath('show', null);
        $this->assertSame(0, $this->event->votes()->count());
    }

    public function test_the_console_shows_every_athlete_and_follows_the_votes(): void
    {
        $this->vote('Ben Otto', 2);

        $this->actingAs($this->admin)
            ->get(route('admin.voting.index', $this->event))
            ->assertOk()
            ->assertSeeInOrder(['Ben Otto', 'Anna Keller', 'Cara Lind'])
            ->assertSee('Vidiwall Show')
            ->assertSee(json_encode(route('admin.voting.screen', $this->event)), false);

        $this->vote('Cara Lind', 3);

        $this->actingAs($this->admin)
            ->getJson(route('admin.voting.screen', $this->event))
            ->assertOk()
            ->assertJsonPath('show', null)
            ->assertJsonPath('blocked', null)
            ->assertJsonPath('voting_closed', false)
            ->assertJsonPath('total', 5)
            ->assertJsonPath('standings.0.name', 'Cara Lind')
            ->assertJsonPath('standings.0.votes', 3);
    }

    public function test_show_settings_are_saved(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.voting.screen.update', $this->event), [
                'headline' => '  Fan MVP ',
                'show_everyone' => 0,
                'top_count' => 3,
                'countdown_seconds' => 8,
                'hold_result' => 0,
                'result_seconds' => 45,
                'show_votes' => 0,
                'show_percent' => 1,
                'show_total' => 0,
                'show_position' => 1,
                'show_qr' => 0,
                'confetti' => 1,
                'close_on_reveal' => 0,
            ])
            ->assertRedirect(route('admin.voting.index', $this->event).'#vidiwall-show')
            ->assertSessionHas('success', 'Vidiwall show settings saved.');

        $screen = $this->event->votingScreen()->first();
        $this->assertSame('Fan MVP', $screen->headline);
        $this->assertSame(3, $screen->top_count);
        $this->assertSame(8, $screen->countdown_seconds);
        $this->assertSame(45, $screen->result_seconds);
        $this->assertFalse($screen->show_votes);
        $this->assertTrue($screen->show_percent);
        $this->assertFalse($screen->show_total);
        $this->assertTrue($screen->show_position);
        $this->assertFalse($screen->show_qr);
        $this->assertTrue($screen->confetti);
        $this->assertFalse($screen->close_on_reveal);

        $this->actingAs($this->admin)
            ->put(route('admin.voting.screen.update', $this->event), [
                'headline' => '',
                'show_everyone' => 1,
                'countdown_seconds' => 0,
                'hold_result' => 1,
            ])
            ->assertSessionHasNoErrors();

        $screen->refresh();
        $this->assertNull($screen->headline);
        $this->assertSame(0, $screen->top_count);
        $this->assertSame(0, $screen->countdown_seconds);
        $this->assertSame(0, $screen->result_seconds);
        $this->assertFalse($screen->confetti);
    }

    public function test_show_settings_are_validated(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.voting.screen.update', $this->event), [
                'headline' => str_repeat('x', 61),
                'show_everyone' => 0,
                'top_count' => 21,
                'countdown_seconds' => 61,
                'hold_result' => 0,
                'result_seconds' => 2,
            ])
            ->assertSessionHasErrors([
                'headline' => 'Keep the headline to 60 characters.',
                'top_count' => 'The live results can show the top 20 places at most. Show everyone for more.',
                'countdown_seconds' => 'The countdown can run for 60 seconds at most.',
                'result_seconds' => 'Show the winner for at least 3 seconds.',
            ]);

        $this->actingAs($this->admin)
            ->put(route('admin.voting.screen.update', $this->event), ['countdown_seconds' => 5])
            ->assertSessionHasErrors(['top_count', 'result_seconds']);
    }

    public function test_moderators_run_the_show_for_their_own_event_only(): void
    {
        $this->vote('Anna Keller', 1);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->get(route('moderator.voting.index', $this->event))
            ->assertOk()
            ->assertSee('Vidiwall Show')
            ->assertSee('Individual Votes')
            ->assertSee(json_encode(route('moderator.voting.screen', $this->event)), false);

        $this->actingAs($moderator)
            ->postJson(route('moderator.voting.screen.reveal', $this->event))
            ->assertOk()
            ->assertJsonPath('show.type', 'reveal');

        $this->actingAs($moderator)
            ->postJson(route('moderator.voting.screen.reveal-now', $this->event))
            ->assertOk()
            ->assertJsonPath('show.phase', 'result');

        $this->actingAs($moderator)
            ->put(route('moderator.voting.screen.update', $this->event), ['show_everyone' => 1, 'countdown_seconds' => 4, 'hold_result' => 1])
            ->assertRedirect(route('moderator.voting.index', $this->event).'#vidiwall-show');

        $other = Event::factory()->create(['module_voting' => true]);

        $this->actingAs($moderator)
            ->postJson(route('moderator.voting.screen.live', $other))
            ->assertForbidden();
    }

    public function test_guests_cannot_control_the_show(): void
    {
        $this->post(route('admin.voting.screen.live', $this->event))->assertRedirect();
        $this->post(route('admin.voting.screen.hide', $this->event))->assertRedirect();

        $this->assertNull($this->event->votingScreen()->first());
    }

    public function test_the_feed_is_quiet_until_the_vote_has_used_the_screen(): void
    {
        $this->getJson(route('vidiwall.voting.feed', $this->event->slug))
            ->assertOk()
            ->assertExactJson(['revision' => 0, 'show' => null]);
    }

    public function test_the_vidiwall_carries_the_vote_overlay_when_the_module_is_on(): void
    {
        $this->get(route('vidiwall.show', $this->event->slug))
            ->assertOk()
            ->assertSee('id="votOverlay"', false)
            ->assertSee('const HAS_VOTING = true;', false);

        $this->event->update(['module_voting' => false]);

        $this->get(route('vidiwall.show', $this->event->slug))
            ->assertOk()
            ->assertDontSee('id="votOverlay"', false);
    }

    private function feed(): TestResponse
    {
        return $this->getJson(route('vidiwall.voting.feed', $this->event->slug))->assertOk();
    }

    private function lotteryFeed(): TestResponse
    {
        return $this->getJson(route('vidiwall.lottery.feed', $this->event->slug))->assertOk();
    }

    private function travelToMs(int $milliseconds): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 20:00:00')->addMilliseconds($milliseconds));
    }

    private function vote(string $candidate, int $count): void
    {
        Vote::factory()->for($this->event)->forCandidate($candidate)->count($count)->create();
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
