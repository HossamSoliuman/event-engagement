<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\User;
use App\Models\VidiwallPlayer;
use App\Models\Vote;
use App\Models\VotingScreen;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VotingFlowTest extends TestCase
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
            'voting_title' => 'Athlete of the Day',
            'voting_options' => [
                ['name' => 'Anna Keller', 'slug' => 'anna-keller', 'position' => 'Downhill', 'image' => null],
                ['name' => 'Ben Otto', 'slug' => 'ben-otto', 'position' => 'Slalom', 'image' => null],
            ],
        ]);
        Carbon::setTestNow('2026-11-11 20:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_new_vote_is_ready_to_start_with_three_minutes(): void
    {
        $this->consoleStatus()
            ->assertJsonPath('stage', 'ready')
            ->assertJsonPath('voting_minutes', 3)
            ->assertJsonPath('ends_in_ms', null)
            ->assertJsonPath('show', null);
    }

    public function test_start_opens_voting_for_the_set_time_with_the_live_results_on_the_wall(): void
    {
        $this->event->update(['voting_closed' => true]);
        $this->event->votingShow()->update(['voting_minutes' => 2]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.start', $this->event))
            ->assertOk()
            ->assertJsonPath('stage', 'voting')
            ->assertJsonPath('voting_closed', false)
            ->assertJsonPath('ends_in_ms', 120000)
            ->assertJsonPath('show.type', 'live')
            ->assertJsonPath('message', 'Voting is open and on the vidiwall.');

        $this->feed()
            ->assertJsonPath('show.type', 'live')
            ->assertJsonPath('show.open', true)
            ->assertJsonPath('show.qr', true)
            ->assertJsonPath('show.ends_in_ms', 120000);

        $this->assertTrue(ActivityLog::query()->where('event_id', $this->event->id)->where('action', 'voting.started')->exists());
    }

    public function test_voting_closes_by_itself_when_the_time_runs_out(): void
    {
        $this->event->votingShow()->update(['voting_minutes' => 1]);
        $this->actingAs($this->admin)->postJson(route('admin.voting.start', $this->event))->assertOk();

        $this->travelToMs(59000);
        $this->feed()
            ->assertJsonPath('show.open', true)
            ->assertJsonPath('show.ends_in_ms', 1000);

        $this->travelToMs(60000);
        $this->feed()
            ->assertJsonPath('show.type', 'live')
            ->assertJsonPath('show.open', false)
            ->assertJsonPath('show.qr', false)
            ->assertJsonPath('show.ends_in_ms', null);

        $this->assertTrue($this->event->fresh()->voting_closed);
        $this->consoleStatus()->assertJsonPath('stage', 'closed');
    }

    public function test_a_vote_after_the_time_is_up_is_turned_away_even_when_nothing_was_watching(): void
    {
        $this->event->votingShow()->update(['voting_minutes' => 1]);
        $this->actingAs($this->admin)->postJson(route('admin.voting.start', $this->event))->assertOk();
        auth()->logout();

        $this->travelToMs(61000);

        $this->postJson(route('vote.store', $this->event->slug), ['candidate' => 'Anna Keller'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Voting is now closed.');

        $this->assertSame(0, $this->event->votes()->count());
    }

    public function test_phones_can_follow_the_vote(): void
    {
        $notVoted = ['voted' => false, 'choice' => null, 'tallies' => null];

        $this->getJson(route('vote.status', $this->event->slug))
            ->assertOk()
            ->assertExactJson(['open' => true, 'ends_in_ms' => null] + $notVoted);

        $this->event->votingShow()->update(['voting_minutes' => 5]);
        $this->actingAs($this->admin)->postJson(route('admin.voting.start', $this->event))->assertOk();

        $this->travelToMs(60000);
        $this->getJson(route('vote.status', $this->event->slug))
            ->assertExactJson(['open' => true, 'ends_in_ms' => 240000] + $notVoted);

        $this->travelToMs(300000);
        $this->getJson(route('vote.status', $this->event->slug))
            ->assertExactJson(['open' => false, 'ends_in_ms' => null] + $notVoted);
    }

    public function test_a_phone_that_voted_gets_its_choice_and_the_live_tallies(): void
    {
        $this->postJson(route('vote.store', $this->event->slug), ['candidate' => 'Ben Otto'])->assertOk();
        $this->vote('Anna Keller', 2);

        $this->getJson(route('vote.status', $this->event->slug))
            ->assertJsonPath('voted', true)
            ->assertJsonPath('choice', 'Ben Otto')
            ->assertJsonPath('tallies.Anna Keller', 2)
            ->assertJsonPath('tallies.Ben Otto', 1);
    }

    public function test_a_guest_votes_once_per_vote_and_again_after_a_new_vote(): void
    {
        $this->postJson(route('vote.store', $this->event->slug), ['candidate' => 'Anna Keller'])->assertOk();
        $this->postJson(route('vote.store', $this->event->slug), ['candidate' => 'Ben Otto'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You have already voted!');

        $this->actingAs($this->admin)->postJson(route('admin.voting.reset', $this->event))->assertOk();
        auth()->logout();

        $this->getJson(route('vote.status', $this->event->slug))
            ->assertJsonPath('voted', false)
            ->assertJsonPath('choice', null);

        $this->postJson(route('vote.store', $this->event->slug), ['candidate' => 'Ben Otto'])
            ->assertOk()
            ->assertJsonPath('tallies.Ben Otto', 1);

        $this->assertSame(['Ben Otto'], $this->event->votes()->pluck('candidate_name')->all());
    }

    public function test_the_vote_page_on_phones_counts_down_and_follows_the_vote(): void
    {
        $this->event->votingShow()->update(['voting_minutes' => 2]);
        $this->actingAs($this->admin)->postJson(route('admin.voting.start', $this->event))->assertOk();
        auth()->logout();

        $this->get(route('event.landing', $this->event->slug))
            ->assertOk()
            ->assertSee('id="voteTimer"', false)
            ->assertSee('Time left to vote')
            ->assertSee('let deadline = 120000;', false)
            ->assertSee('/vote/status', false);
    }

    public function test_the_status_for_phones_needs_the_vote_to_be_on(): void
    {
        $this->event->update(['module_voting' => false]);

        $this->getJson(route('vote.status', $this->event->slug))->assertNotFound();
    }

    public function test_voting_without_a_timer_stays_open_until_it_is_ended(): void
    {
        $this->event->votingShow()->update(['voting_minutes' => 0]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.start', $this->event))
            ->assertOk()
            ->assertJsonPath('ends_in_ms', null);

        $this->travelToMs(3600000);
        $this->feed()
            ->assertJsonPath('show.open', true)
            ->assertJsonPath('show.ends_in_ms', null);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.close', $this->event))
            ->assertOk()
            ->assertJsonPath('stage', 'closed')
            ->assertJsonPath('voting_closed', true)
            ->assertJsonPath('message', 'Voting is closed.');

        $this->feed()
            ->assertJsonPath('show.type', 'live')
            ->assertJsonPath('show.open', false);
    }

    public function test_one_more_minute_moves_the_end_of_voting(): void
    {
        $this->event->votingShow()->update(['voting_minutes' => 1]);
        $this->actingAs($this->admin)->postJson(route('admin.voting.start', $this->event))->assertOk();

        $this->travelToMs(30000);
        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.extend', $this->event))
            ->assertOk()
            ->assertJsonPath('ends_in_ms', 90000)
            ->assertJsonPath('message', 'One more minute to vote.');

        $this->travelToMs(100000);
        $this->feed()->assertJsonPath('show.open', true);

        $this->travelToMs(120000);
        $this->feed()->assertJsonPath('show.open', false);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.extend', $this->event))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Voting has no timer running.');
    }

    public function test_the_vote_goes_through_its_steps_and_starts_over(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.voting.start', $this->event))->assertJsonPath('stage', 'voting');
        $this->vote('Ben Otto', 2);

        $this->actingAs($this->admin)->postJson(route('admin.voting.close', $this->event))->assertJsonPath('stage', 'closed');

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.reveal', $this->event))
            ->assertOk()
            ->assertJsonPath('stage', 'winner')
            ->assertJsonPath('show.winners', ['Ben Otto']);

        $this->travelToMs(VotingScreen::INTRO_MS + 5000);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.podium', $this->event))
            ->assertOk()
            ->assertJsonPath('stage', 'podium');

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.screen.finish', $this->event))
            ->assertOk()
            ->assertJsonPath('stage', 'done')
            ->assertJsonPath('show', null)
            ->assertJsonPath('message', 'Done. The playlist is back on the vidiwall.');

        $this->feed()->assertJsonPath('show', null);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.reset', $this->event))
            ->assertOk()
            ->assertJsonPath('stage', 'ready')
            ->assertJsonPath('voting_closed', false)
            ->assertJsonPath('total', 0)
            ->assertJsonPath('message', 'All votes cleared. Ready for a new vote.');

        $this->assertSame(0, $this->event->votes()->count());
    }

    public function test_a_timed_winner_leaving_the_wall_keeps_the_vote_at_the_winner(): void
    {
        $this->vote('Anna Keller', 1);
        $this->event->votingShow()->update(['countdown_seconds' => 0, 'result_seconds' => 5]);
        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.reveal', $this->event))->assertOk();

        $this->travelToMs(VotingScreen::INTRO_MS + 6000);

        $this->consoleStatus()
            ->assertJsonPath('show', null)
            ->assertJsonPath('stage', 'winner');
    }

    public function test_a_started_vote_cannot_be_started_again(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.voting.start', $this->event))->assertOk();

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.start', $this->event))
            ->assertStatus(409)
            ->assertJsonPath('message', 'This vote has already started. Start a new vote first.');
    }

    public function test_starting_needs_athletes(): void
    {
        $this->event->update(['voting_options' => []]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.start', $this->event))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Add athletes to the vote in the event settings first.');

        $this->assertSame(VotingScreen::STAGE_READY, $this->event->votingShow()->fresh()->stage);
    }

    public function test_the_playlist_waits_for_the_vote_and_carries_on_when_it_is_done(): void
    {
        $this->upload();
        $this->event->player()->play();

        $this->actingAs($this->admin)->postJson(route('admin.voting.start', $this->event))->assertOk();
        $this->assertSame(VidiwallPlayer::STATE_PAUSED, $this->event->vidiwallPlayer()->first()->state);

        $this->actingAs($this->admin)->postJson(route('admin.voting.screen.finish', $this->event))->assertOk();
        $this->assertSame(VidiwallPlayer::STATE_PLAYING, $this->event->vidiwallPlayer()->first()->state);
    }

    public function test_reopening_after_the_timer_ran_out_keeps_voting_open(): void
    {
        $this->event->votingShow()->update(['voting_minutes' => 1]);
        $this->actingAs($this->admin)->postJson(route('admin.voting.start', $this->event))->assertOk();
        $this->travelToMs(90000);
        $this->feed()->assertJsonPath('show.open', false);

        $this->actingAs($this->admin)->post(route('admin.voting.reopen', $this->event))->assertRedirect();

        $this->travelToMs(600000);
        $this->feed()
            ->assertJsonPath('show.open', true)
            ->assertJsonPath('show.ends_in_ms', null);
    }

    public function test_the_voting_time_is_saved_and_checked(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.timer', $this->event), ['minutes' => 5])
            ->assertOk()
            ->assertJsonPath('voting_minutes', 5);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.timer', $this->event), ['minutes' => 0])
            ->assertOk()
            ->assertJsonPath('voting_minutes', 0);

        $this->actingAs($this->admin)
            ->postJson(route('admin.voting.timer', $this->event), ['minutes' => 4])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Pick one of the voting times on offer.');

        $this->assertSame(0, $this->event->votingShow()->fresh()->voting_minutes);
    }

    public function test_screen_options_save_from_the_page_without_a_reload(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.voting.screen.update', $this->event), [
                'headline' => 'Fan MVP',
                'show_everyone' => 1,
                'countdown_seconds' => 5,
                'hold_result' => 1,
                'show_qr' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('stage', 'ready');

        $this->assertSame('Fan MVP', $this->event->votingShow()->fresh()->headline);

        $this->actingAs($this->admin)
            ->putJson(route('admin.voting.screen.update', $this->event), ['countdown_seconds' => 99, 'show_everyone' => 1, 'hold_result' => 1])
            ->assertStatus(422)
            ->assertJsonPath('message', 'The countdown can run for 60 seconds at most.');
    }

    public function test_the_console_shows_the_steps_and_the_screen_options(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.voting.index', $this->event))
            ->assertOk()
            ->assertSee('Start voting')
            ->assertSee('Screen options')
            ->assertSee(json_encode(route('admin.voting.start', $this->event)), false)
            ->assertDontSee('Danger Zone');
    }

    public function test_moderators_run_the_vote_for_their_own_event_only(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->get(route('moderator.voting.index', $this->event))
            ->assertOk()
            ->assertSee(json_encode(route('moderator.voting.start', $this->event)), false);

        $this->actingAs($moderator)
            ->postJson(route('moderator.voting.timer', $this->event), ['minutes' => 2])
            ->assertOk();

        $this->actingAs($moderator)
            ->postJson(route('moderator.voting.start', $this->event))
            ->assertOk()
            ->assertJsonPath('ends_in_ms', 120000);

        $this->actingAs($moderator)
            ->postJson(route('moderator.voting.extend', $this->event))
            ->assertOk()
            ->assertJsonPath('ends_in_ms', 180000);

        $this->actingAs($moderator)
            ->postJson(route('moderator.voting.screen.finish', $this->event))
            ->assertOk()
            ->assertJsonPath('stage', 'done');

        $other = Event::factory()->create(['module_voting' => true]);

        $this->actingAs($moderator)
            ->postJson(route('moderator.voting.start', $other))
            ->assertForbidden();
    }

    public function test_guests_cannot_run_the_vote(): void
    {
        $this->post(route('admin.voting.start', $this->event))->assertRedirect();
        $this->post(route('admin.voting.extend', $this->event))->assertRedirect();
        $this->post(route('admin.voting.timer', $this->event), ['minutes' => 1])->assertRedirect();
        $this->post(route('admin.voting.screen.finish', $this->event))->assertRedirect();

        $this->assertNull($this->event->votingScreen()->first());
        $this->assertFalse($this->event->fresh()->voting_closed);
    }

    private function consoleStatus(): TestResponse
    {
        return $this->actingAs($this->admin)->getJson(route('admin.voting.screen', $this->event))->assertOk();
    }

    private function feed(): TestResponse
    {
        return $this->getJson(route('vidiwall.voting.feed', $this->event->slug))->assertOk();
    }

    private function travelToMs(int $milliseconds): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 20:00:00')->addMilliseconds($milliseconds));
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
