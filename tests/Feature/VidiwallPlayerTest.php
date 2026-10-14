<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\User;
use App\Models\VidiwallPlayer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VidiwallPlayerTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->event = Event::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_player_page_renders_for_admins_and_assigned_moderators(): void
    {
        $this->upload('pending');

        $this->actingAs($this->admin)
            ->get(route('admin.player.index', $this->event))
            ->assertOk()
            ->assertSee('Vidiwall Player')
            ->assertSee(json_encode(route('admin.player.play', $this->event)), false)
            ->assertSee(route('admin.fotos.index', [$this->event, 'status' => 'pending']))
            ->assertSee('data-count="pending">1</span>', false);

        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->get(route('moderator.player.index', $this->event))
            ->assertOk()
            ->assertSee(json_encode(route('moderator.player.play', $this->event)), false)
            ->assertSee(route('moderator.fotos.index', [$this->event, 'status' => 'pending']));
    }

    public function test_nothing_plays_until_an_operator_presses_play(): void
    {
        $first = $this->upload();
        $this->upload();

        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto', null);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertOk()
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.id', $first->id)
            ->assertJsonPath('counts.total', 2)
            ->assertJsonPath('counts.unplayed', 1);

        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('state', 'playing')
            ->assertJsonPath('foto.id', $first->id);
    }

    public function test_play_with_an_empty_playlist_says_there_is_nothing_to_play(): void
    {
        $this->upload('pending');

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Nothing to play yet. Approve photos or videos first.')
            ->assertJsonPath('player.state', 'stopped');
    }

    public function test_pause_freezes_the_current_item_and_play_resumes_where_it_left_off(): void
    {
        $first = $this->upload();
        $second = $this->upload();

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->actingAs($this->admin)->postJson(route('admin.player.play', $this->event));

        Carbon::setTestNow('2026-10-12 20:00:02.500');
        $this->actingAs($this->admin)
            ->postJson(route('admin.player.pause', $this->event))
            ->assertJsonPath('player.state', 'paused')
            ->assertJsonPath('player.elapsed_ms', 2500);

        Carbon::setTestNow('2026-10-12 20:05:00');
        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('state', 'paused')
            ->assertJsonPath('foto.id', $first->id)
            ->assertJsonPath('elapsed_ms', 2500);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.id', $first->id)
            ->assertJsonPath('player.elapsed_ms', 2500);

        Carbon::setTestNow('2026-10-12 20:05:01.400');
        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.id', $first->id);

        Carbon::setTestNow('2026-10-12 20:05:01.500');
        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.id', $second->id);
    }

    public function test_stop_clears_the_screen_and_play_starts_over_from_the_top(): void
    {
        $first = $this->upload();
        $second = $this->upload();

        $this->event->player()->playFrom($second);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.stop', $this->event))
            ->assertJsonPath('player.state', 'stopped')
            ->assertJsonPath('now_playing', null);

        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto', null);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('now_playing.id', $first->id);
    }

    public function test_next_and_previous_step_through_the_playlist(): void
    {
        $first = $this->upload();
        $second = $this->upload();
        $third = $this->upload();

        $this->actingAs($this->admin)->postJson(route('admin.player.play', $this->event));

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))->assertJsonPath('now_playing.id', $second->id);
        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))->assertJsonPath('now_playing.id', $third->id);
        $this->actingAs($this->admin)->postJson(route('admin.player.previous', $this->event))->assertJsonPath('now_playing.id', $second->id);
        $this->actingAs($this->admin)->postJson(route('admin.player.previous', $this->event))->assertJsonPath('now_playing.id', $first->id);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.previous', $this->event))
            ->assertJsonPath('now_playing.id', $first->id)
            ->assertJsonPath('player.state', 'playing');
    }

    public function test_next_past_the_last_item_stops_without_loop(): void
    {
        $this->upload();
        $last = $this->upload();

        $this->event->player()->playFrom($last);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('player.state', 'stopped')
            ->assertJsonPath('now_playing', null);
    }

    public function test_stepping_while_paused_cues_the_item_without_playing_it(): void
    {
        $this->upload();
        $second = $this->upload();

        $this->event->player()->play();
        $this->event->player()->pause();

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('player.state', 'paused')
            ->assertJsonPath('now_playing.id', $second->id)
            ->assertJsonPath('player.elapsed_ms', 0);
    }

    public function test_next_and_previous_do_nothing_while_stopped(): void
    {
        $this->upload();

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))->assertJsonPath('now_playing', null);
        $this->actingAs($this->admin)->postJson(route('admin.player.previous', $this->event))->assertJsonPath('now_playing', null);
    }

    public function test_loop_wraps_around_to_the_first_item_after_the_last(): void
    {
        $first = $this->upload();
        $last = $this->upload();

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.loop', $this->event), ['loop' => true])
            ->assertOk()
            ->assertJsonPath('player.loop', true);

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->event->player()->playFrom($last);

        Carbon::setTestNow('2026-10-12 20:00:04');
        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('state', 'playing')
            ->assertJsonPath('foto.id', $first->id);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.previous', $this->event))
            ->assertJsonPath('now_playing.id', $last->id);
    }

    public function test_loop_requires_a_boolean(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.player.loop', $this->event), ['loop' => 'sometimes'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Choose whether the playlist should loop.');
    }

    public function test_photo_seconds_change_re_times_the_photo_on_screen(): void
    {
        $this->event->update(['vidiwall_photo_seconds' => 4]);
        $photo = $this->upload();

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->event->player()->playFrom($photo);
        $revision = $this->event->player()->revision;

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photo-seconds', $this->event), ['seconds' => 10])
            ->assertOk()
            ->assertJsonPath('player.photo_seconds', 10)
            ->assertJsonPath('player.revision', $revision + 1)
            ->assertJsonPath('now_playing.slot_ms', 10000);

        $this->assertSame(10, $this->event->fresh()->vidiwall_photo_seconds);
        $this->assertDatabaseHas('activity_log', ['event_id' => $this->event->id, 'action' => 'vidiwall.photo_seconds_changed']);

        Carbon::setTestNow('2026-10-12 20:00:06');
        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('foto.id', $photo->id)
            ->assertJsonPath('foto.slot_ms', 10000);
    }

    public function test_shortening_photo_seconds_moves_on_from_a_photo_past_the_new_length(): void
    {
        $this->event->update(['vidiwall_photo_seconds' => 10]);
        $first = $this->upload();
        $second = $this->upload();

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->event->player()->playFrom($first);

        Carbon::setTestNow('2026-10-12 20:00:05');
        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photo-seconds', $this->event), ['seconds' => 3])
            ->assertOk()
            ->assertJsonPath('now_playing.id', $second->id);
    }

    public function test_photo_seconds_unchanged_leaves_the_revision_alone(): void
    {
        $this->event->update(['vidiwall_photo_seconds' => 4]);
        $this->event->player()->playFrom($this->upload());
        $revision = $this->event->player()->revision;

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photo-seconds', $this->event), ['seconds' => 4])
            ->assertOk()
            ->assertJsonPath('player.revision', $revision);
    }

    public function test_photo_seconds_change_refreshes_the_playlist_version(): void
    {
        $this->upload();
        $version = $this->actingAs($this->admin)->getJson(route('admin.player.status', $this->event))->json('version');

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photo-seconds', $this->event), ['seconds' => 7, 'v' => $version])
            ->assertOk()
            ->assertJsonPath('items.0.slot_ms', 7000);
    }

    public function test_photo_seconds_must_be_a_whole_number_between_1_and_60(): void
    {
        $this->event->update(['vidiwall_photo_seconds' => 4]);

        foreach ([
            [[], 'Enter how many seconds each photo stays on screen.'],
            [['seconds' => 2.5], 'Photo seconds must be a whole number.'],
            [['seconds' => 0], 'Photos need at least 1 second on screen.'],
            [['seconds' => 61], 'Photos can stay on screen for 60 seconds at most.'],
        ] as [$payload, $message]) {
            $this->actingAs($this->admin)
                ->postJson(route('admin.player.photo-seconds', $this->event), $payload)
                ->assertUnprocessable()
                ->assertJsonPath('message', $message);
        }

        $this->assertSame(4, $this->event->fresh()->vidiwall_photo_seconds);
    }

    public function test_assigned_moderators_can_change_photo_seconds_but_others_cannot(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->postJson(route('moderator.player.photo-seconds', $this->event), ['seconds' => 12])
            ->assertOk()
            ->assertJsonPath('player.photo_seconds', 12);

        $outsider = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($outsider)
            ->postJson(route('moderator.player.photo-seconds', $this->event), ['seconds' => 20])
            ->assertForbidden();

        $this->assertSame(12, $this->event->fresh()->vidiwall_photo_seconds);
    }

    public function test_play_from_jumps_to_the_item_and_continues_from_there(): void
    {
        $this->upload();
        $second = $this->upload();
        $third = $this->upload();

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play-from', [$this->event, $second]))
            ->assertOk()
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.id', $second->id);

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))->assertJsonPath('now_playing.id', $third->id);
    }

    public function test_play_next_moves_the_item_right_after_the_current_one(): void
    {
        $first = $this->upload();
        $second = $this->upload();
        $third = $this->upload();
        $fourth = $this->upload();

        $this->event->player()->playFrom($second);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play-next', [$this->event, $fourth]))
            ->assertOk()
            ->assertJsonPath('now_playing.id', $second->id)
            ->assertJsonPath('items.2.id', $fourth->id);

        $this->assertSame([$first->id, $second->id, $fourth->id, $third->id], $this->playlistIds());

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))->assertJsonPath('now_playing.id', $fourth->id);
    }

    public function test_play_next_while_stopped_moves_the_item_to_the_top(): void
    {
        $first = $this->upload();
        $second = $this->upload();

        $this->actingAs($this->admin)->postJson(route('admin.player.play-next', [$this->event, $second]))->assertOk();

        $this->assertSame([$second->id, $first->id], $this->playlistIds());
        $this->actingAs($this->admin)->postJson(route('admin.player.play', $this->event))->assertJsonPath('now_playing.id', $second->id);
    }

    public function test_item_actions_refuse_items_that_are_not_approved(): void
    {
        $pending = $this->upload('pending');

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play-from', [$this->event, $pending]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'That item is no longer approved.');

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play-next', [$this->event, $pending]))
            ->assertStatus(422);
    }

    public function test_item_actions_are_scoped_to_the_event(): void
    {
        $otherEvent = Event::factory()->create();
        $foreign = $this->upload('approved', $otherEvent);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play-from', [$this->event, $foreign]))
            ->assertNotFound();
    }

    public function test_reorder_saves_the_dragged_order(): void
    {
        $first = $this->upload();
        $second = $this->upload();
        $third = $this->upload();

        $this->actingAs($this->admin)
            ->putJson(route('admin.player.reorder', $this->event), ['order' => [$third->id, $first->id, $second->id]])
            ->assertOk()
            ->assertJsonPath('items.0.id', $third->id);

        $this->assertSame([$third->id, $first->id, $second->id], $this->playlistIds());
        $this->actingAs($this->admin)->postJson(route('admin.player.play', $this->event))->assertJsonPath('now_playing.id', $third->id);
    }

    public function test_reorder_keeps_items_approved_meanwhile_at_the_end_and_ignores_unknown_ids(): void
    {
        $first = $this->upload();
        $second = $this->upload();
        $rejected = $this->upload('rejected');
        $foreign = $this->upload('approved', Event::factory()->create());
        $approvedMeanwhile = $this->upload();

        $this->actingAs($this->admin)
            ->putJson(route('admin.player.reorder', $this->event), ['order' => [$second->id, $rejected->id, $foreign->id, $first->id]])
            ->assertOk();

        $this->assertSame([$second->id, $first->id, $approvedMeanwhile->id], $this->playlistIds());
    }

    public function test_reorder_validates_the_order(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.player.reorder', $this->event), ['order' => ['abc']])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The playlist order is invalid.');

        $this->actingAs($this->admin)
            ->putJson(route('admin.player.reorder', $this->event), [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Send the playlist in its new order.');
    }

    public function test_playback_follows_a_new_order_made_while_playing(): void
    {
        $first = $this->upload();
        $second = $this->upload();
        $third = $this->upload();

        $this->event->player()->play();
        $this->event->player()->reorder([$first->id, $third->id, $second->id]);

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))->assertJsonPath('now_playing.id', $third->id);
    }

    public function test_newly_approved_items_join_the_end_of_a_running_playlist(): void
    {
        $first = $this->upload();
        $pending = $this->upload('pending');

        $this->event->player()->play();
        $this->actingAs($this->admin)->post(route('admin.fotos.approve', $pending));

        $this->assertSame([$first->id, $pending->id], $this->playlistIds());
        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))->assertJsonPath('now_playing.id', $pending->id);
    }

    public function test_status_only_sends_the_playlist_when_it_changed(): void
    {
        $this->upload();

        $version = $this->actingAs($this->admin)
            ->getJson(route('admin.player.status', $this->event))
            ->assertJsonCount(1, 'items')
            ->json('version');

        $this->actingAs($this->admin)
            ->getJson(route('admin.player.status', [$this->event, 'v' => $version]))
            ->assertJsonMissingPath('items');

        $this->upload();

        $this->actingAs($this->admin)
            ->getJson(route('admin.player.status', [$this->event, 'v' => $version]))
            ->assertJsonCount(2, 'items');
    }

    public function test_status_reports_the_foto_queue_tab_counts(): void
    {
        $this->upload('pending');
        $this->upload('pending');
        $this->upload();
        $this->upload('rejected');
        $this->upload('pending', Event::factory()->create());

        $this->actingAs($this->admin)
            ->getJson(route('admin.player.status', $this->event))
            ->assertJsonPath('counts.pending', 2)
            ->assertJsonPath('counts.approved', 1)
            ->assertJsonPath('counts.rejected', 1)
            ->assertJsonPath('counts.total', 1)
            ->assertJsonPath('player.state', 'stopped');
    }

    public function test_every_playback_change_bumps_the_revision(): void
    {
        $this->upload();
        $this->upload();

        $revisions = collect(['play', 'pause', 'play', 'next', 'stop'])->map(function (string $action) {
            return $this->actingAs($this->admin)->postJson(route('admin.player.'.$action, $this->event))->json('player.revision');
        });

        $this->assertSame($revisions->sort()->values()->all(), $revisions->all());
        $this->assertSame($revisions->count(), $revisions->unique()->count());
    }

    public function test_deleting_the_item_on_screen_moves_the_player_on(): void
    {
        Storage::fake('media');
        $first = $this->upload();
        $second = $this->upload();

        $this->event->player()->play();

        $this->actingAs($this->admin)->delete(route('admin.fotos.destroy', $first))->assertRedirect();

        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.id', $second->id);
    }

    public function test_feed_lets_a_screen_join_mid_item(): void
    {
        $video = $this->upload('approved', null, ['media_type' => 'video', 'video_path' => 'fotos/clip.mp4', 'video_duration' => 20]);

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->event->player()->play();

        Carbon::setTestNow('2026-10-12 20:00:07.250');
        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('foto.id', $video->id)
            ->assertJsonPath('foto.slot_ms', 20000)
            ->assertJsonPath('elapsed_ms', 7250);
    }

    public function test_player_controls_require_a_login(): void
    {
        $this->postJson(route('admin.player.play', $this->event))->assertUnauthorized();
        $this->getJson(route('admin.player.status', $this->event))->assertUnauthorized();

        $this->assertNull($this->event->vidiwallPlayer()->first());
    }

    public function test_moderators_control_only_their_assigned_events(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);
        $foto = $this->upload();
        $otherEvent = Event::factory()->create();

        $this->actingAs($moderator)
            ->postJson(route('moderator.player.play', $this->event))
            ->assertOk()
            ->assertJsonPath('now_playing.id', $foto->id);

        $this->actingAs($moderator)->postJson(route('moderator.player.play', $otherEvent))->assertForbidden();
    }

    public function test_each_event_gets_a_single_player(): void
    {
        $this->event->player();
        $this->event->fresh()->player();

        $this->assertSame(1, VidiwallPlayer::query()->where('event_id', $this->event->id)->count());
    }

    public function test_play_while_playing_keeps_the_item_running(): void
    {
        $first = $this->upload();
        $this->upload();

        Carbon::setTestNow('2026-10-12 20:00:00');
        $revision = $this->actingAs($this->admin)->postJson(route('admin.player.play', $this->event))->json('player.revision');

        Carbon::setTestNow('2026-10-12 20:00:02');
        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('now_playing.id', $first->id)
            ->assertJsonPath('player.elapsed_ms', 2000)
            ->assertJsonPath('player.revision', $revision);
    }

    public function test_pausing_after_the_item_ran_out_moves_on_when_resumed(): void
    {
        $first = $this->upload();
        $second = $this->upload();

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->event->player()->play();

        Carbon::setTestNow('2026-10-12 20:00:06');
        $this->actingAs($this->admin)
            ->postJson(route('admin.player.pause', $this->event))
            ->assertJsonPath('player.state', 'paused')
            ->assertJsonPath('now_playing.id', $first->id)
            ->assertJsonPath('player.elapsed_ms', 4000);

        Carbon::setTestNow('2026-10-12 20:01:00');
        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.id', $second->id)
            ->assertJsonPath('player.elapsed_ms', 0);
    }

    public function test_previous_on_the_first_item_restarts_it(): void
    {
        $first = $this->upload();
        $this->upload();

        Carbon::setTestNow('2026-10-12 20:00:00');
        $revision = $this->actingAs($this->admin)->postJson(route('admin.player.play', $this->event))->json('player.revision');

        Carbon::setTestNow('2026-10-12 20:00:03');
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.player.previous', $this->event))
            ->assertJsonPath('now_playing.id', $first->id)
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('player.elapsed_ms', 0);

        $this->assertGreaterThan($revision, $response->json('player.revision'));
    }

    public function test_previous_on_the_first_item_wraps_to_the_last_when_looping(): void
    {
        $this->upload();
        $this->upload();
        $last = $this->upload();
        $this->event->player()->setLoop(true);
        $this->event->player()->play();

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.previous', $this->event))
            ->assertJsonPath('now_playing.id', $last->id)
            ->assertJsonPath('player.state', 'playing');
    }

    public function test_a_single_looping_item_replays_after_the_screen_reports_it_done(): void
    {
        $only = $this->upload();
        $this->event->player()->setLoop(true);

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->event->player()->play();
        $revision = $this->getJson(route('vidiwall.feed', $this->event->slug))->json('revision');

        Carbon::setTestNow('2026-10-12 20:00:04');
        $response = $this->getJson(route('vidiwall.feed', ['slug' => $this->event->slug, 'done' => $only->id, 'rev' => $revision]))
            ->assertJsonPath('state', 'playing')
            ->assertJsonPath('foto.id', $only->id)
            ->assertJsonPath('elapsed_ms', 0)
            ->assertJsonPath('next', null);

        $this->assertGreaterThan($revision, $response->json('revision'));
    }

    public function test_a_repeated_done_report_moves_on_only_once(): void
    {
        $first = $this->upload();
        $second = $this->upload();
        $this->upload();

        $this->event->player()->play();
        $revision = $this->getJson(route('vidiwall.feed', $this->event->slug))->json('revision');
        $done = ['slug' => $this->event->slug, 'done' => $first->id, 'rev' => $revision];

        $this->getJson(route('vidiwall.feed', $done))->assertJsonPath('foto.id', $second->id);
        $this->getJson(route('vidiwall.feed', $done))->assertJsonPath('foto.id', $second->id);
    }

    public function test_a_done_report_while_paused_is_ignored(): void
    {
        $first = $this->upload();
        $this->upload();

        $this->event->player()->play();
        $revision = $this->getJson(route('vidiwall.feed', $this->event->slug))->json('revision');
        $this->event->player()->pause();

        $this->getJson(route('vidiwall.feed', ['slug' => $this->event->slug, 'done' => $first->id, 'rev' => $revision]))
            ->assertJsonPath('state', 'paused')
            ->assertJsonPath('foto.id', $first->id);

        $paused = $this->event->player()->fresh();
        $this->getJson(route('vidiwall.feed', ['slug' => $this->event->slug, 'done' => $first->id, 'rev' => $paused->revision]))
            ->assertJsonPath('state', 'paused')
            ->assertJsonPath('foto.id', $first->id);
    }

    public function test_a_video_moves_on_as_soon_as_the_screen_reports_it_done(): void
    {
        $video = $this->upload('approved', null, ['media_type' => 'video', 'video_path' => 'fotos/clip.mp4', 'video_duration' => 8]);
        $photo = $this->upload();

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->event->player()->play();
        $revision = $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.slot_ms', 8000)->json('revision');

        Carbon::setTestNow('2026-10-12 20:00:07');
        $this->getJson(route('vidiwall.feed', ['slug' => $this->event->slug, 'done' => $video->id, 'rev' => $revision]))
            ->assertJsonPath('foto.id', $photo->id);
    }

    public function test_photo_seconds_leave_the_video_slot_alone(): void
    {
        $this->upload('approved', null, ['media_type' => 'video', 'video_path' => 'fotos/clip.mp4', 'video_duration' => 3]);
        $this->event->player()->play();

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photo-seconds', $this->event), ['seconds' => 20])
            ->assertJsonPath('player.photo_seconds', 20)
            ->assertJsonPath('now_playing.slot_ms', 3000);
    }

    public function test_lengthening_photo_seconds_while_paused_keeps_the_position(): void
    {
        $first = $this->upload();
        $second = $this->upload();

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->event->player()->play();

        Carbon::setTestNow('2026-10-12 20:00:02.500');
        $this->event->player()->pause();

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photo-seconds', $this->event), ['seconds' => 10])
            ->assertJsonPath('player.state', 'paused')
            ->assertJsonPath('player.elapsed_ms', 2500)
            ->assertJsonPath('now_playing.slot_ms', 10000);

        Carbon::setTestNow('2026-10-12 20:01:00');
        $this->actingAs($this->admin)->postJson(route('admin.player.play', $this->event));

        Carbon::setTestNow('2026-10-12 20:01:07.400');
        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.id', $first->id);

        Carbon::setTestNow('2026-10-12 20:01:07.500');
        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.id', $second->id);
    }

    public function test_shortening_photo_seconds_below_the_paused_position_moves_on_when_resumed(): void
    {
        $this->event->update(['vidiwall_photo_seconds' => 10]);
        $this->upload();
        $second = $this->upload();

        Carbon::setTestNow('2026-10-12 20:00:00');
        $this->event->player()->play();

        Carbon::setTestNow('2026-10-12 20:00:06');
        $this->event->player()->pause();

        $this->actingAs($this->admin)->postJson(route('admin.player.photo-seconds', $this->event), ['seconds' => 3]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.id', $second->id);
    }

    public function test_next_while_paused_on_the_last_item_wraps_paused_when_looping(): void
    {
        $first = $this->upload();
        $last = $this->upload();
        $this->event->player()->setLoop(true);
        $this->event->player()->playFrom($last);
        $this->event->player()->pause();

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('player.state', 'paused')
            ->assertJsonPath('now_playing.id', $first->id)
            ->assertJsonPath('player.elapsed_ms', 0);
    }

    public function test_play_from_while_paused_starts_playing(): void
    {
        $this->upload();
        $second = $this->upload();
        $this->event->player()->play();
        $this->event->player()->pause();

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play-from', [$this->event, $second]))
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.id', $second->id);
    }

    public function test_rejecting_the_paused_item_cues_the_next_one_paused(): void
    {
        $first = $this->upload();
        $second = $this->upload();
        $this->event->player()->play();
        $this->event->player()->pause();

        $this->actingAs($this->admin)->postJson(route('admin.fotos.reject', $first))->assertOk();

        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('state', 'paused')
            ->assertJsonPath('foto.id', $second->id)
            ->assertJsonPath('elapsed_ms', 0);
    }

    public function test_feed_names_the_item_due_next_so_the_screen_can_load_it_early(): void
    {
        $first = $this->upload();
        $second = $this->upload();

        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('next.id', $first->id);

        $this->event->player()->play();
        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('foto.id', $first->id)
            ->assertJsonPath('next.id', $second->id)
            ->assertJsonPath('next.media_type', 'photo');

        $this->event->player()->next();
        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('next', null);

        $this->event->player()->setLoop(true);
        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('next.id', $first->id);

        $this->event->player()->stop();
        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('next.id', $first->id);
    }

    public function test_one_photo_per_screen_is_the_default(): void
    {
        $first = $this->upload();
        $this->upload();

        $this->assertSame(1, $this->event->fresh()->vidiwall_photos_per_screen);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('player.photos_per_screen', 1)
            ->assertJsonPath('now_playing.page_ids', [$first->id]);

        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('layout', 1)
            ->assertJsonPath('page.0.id', $first->id)
            ->assertJsonCount(1, 'page');
    }

    public function test_photos_per_screen_accepts_1_2_and_4(): void
    {
        foreach ([2, 4, 1] as $count) {
            $this->actingAs($this->admin)
                ->postJson(route('admin.player.photos-per-screen', $this->event), ['count' => $count])
                ->assertOk()
                ->assertJsonPath('player.photos_per_screen', $count);

            $this->assertSame($count, $this->event->fresh()->vidiwall_photos_per_screen);
        }

        $this->assertDatabaseHas('activity_log', ['event_id' => $this->event->id, 'action' => 'vidiwall.photos_per_screen_changed']);
    }

    public function test_photos_per_screen_refuses_anything_but_1_2_or_4(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 2]);

        foreach ([
            [[], 'Choose how many photos show on screen at once.'],
            [['count' => 'four'], 'Photos on screen must be a whole number.'],
            [['count' => 2.5], 'Photos on screen must be a whole number.'],
            [['count' => 0], 'The screen can show 1, 2 or 4 photos at once.'],
            [['count' => 3], 'The screen can show 1, 2 or 4 photos at once.'],
            [['count' => 5], 'The screen can show 1, 2 or 4 photos at once.'],
        ] as [$payload, $message]) {
            $this->actingAs($this->admin)
                ->postJson(route('admin.player.photos-per-screen', $this->event), $payload)
                ->assertUnprocessable()
                ->assertJsonPath('message', $message);
        }

        $this->assertSame(2, $this->event->fresh()->vidiwall_photos_per_screen);
    }

    public function test_assigned_moderators_can_change_photos_per_screen_but_others_cannot(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->postJson(route('moderator.player.photos-per-screen', $this->event), ['count' => 4])
            ->assertOk()
            ->assertJsonPath('player.photos_per_screen', 4);

        $outsider = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($outsider)
            ->postJson(route('moderator.player.photos-per-screen', $this->event), ['count' => 2])
            ->assertForbidden();

        $this->assertSame(4, $this->event->fresh()->vidiwall_photos_per_screen);
    }

    public function test_four_per_screen_plays_the_first_four_photos_together(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(6);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('now_playing.id', $photos[0]->id)
            ->assertJsonPath('now_playing.page_ids', $this->ids($photos, 0, 4))
            ->assertJsonCount(4, 'now_playing.page');

        $feed = $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('layout', 4)
            ->assertJsonPath('foto.id', $photos[0]->id)
            ->assertJsonPath('foto.slot_ms', 4000)
            ->assertJsonPath('page.1.uploader', 'Guest')
            ->assertJsonPath('next.id', $photos[4]->id);

        $this->assertSame($this->ids($photos, 0, 4), array_column($feed->json('page'), 'id'));
        $this->assertSame($this->ids($photos, 4, 2), array_column($feed->json('next_page'), 'id'));
    }

    public function test_the_done_report_for_the_playhead_moves_on_to_the_next_four(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(8);

        $this->event->player()->play();
        $revision = $this->getJson(route('vidiwall.feed', $this->event->slug))->json('revision');

        $feed = $this->getJson(route('vidiwall.feed', ['slug' => $this->event->slug, 'done' => $photos[0]->id, 'rev' => $revision]))
            ->assertJsonPath('foto.id', $photos[4]->id)
            ->assertJsonPath('next', null);

        $this->assertSame($this->ids($photos, 4, 4), array_column($feed->json('page'), 'id'));
    }

    public function test_the_page_holds_for_one_photo_slot_when_no_screen_reports(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 2, 'vidiwall_photo_seconds' => 5]);
        $photos = $this->photos(4);

        Carbon::setTestNow('2026-10-14 20:00:00');
        $this->event->player()->play();

        Carbon::setTestNow('2026-10-14 20:00:04.900');
        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.id', $photos[0]->id);

        Carbon::setTestNow('2026-10-14 20:00:05');
        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('foto.id', $photos[2]->id)
            ->assertJsonPath('page.1.id', $photos[3]->id);
    }

    public function test_a_video_ends_the_page_before_it_and_plays_alone(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        [$a, $b] = $this->photos(2);
        $video = $this->video();
        $after = $this->photos(5);

        $this->actingAs($this->admin)->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('now_playing.page_ids', [$a->id, $b->id]);

        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('next_page.0.id', $video->id)->assertJsonCount(1, 'next_page');

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('now_playing.page_ids', [$video->id])
            ->assertJsonPath('now_playing.is_video', true);

        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('foto.slot_ms', 5000)
            ->assertJsonCount(1, 'page');

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('now_playing.page_ids', $this->ids($after, 0, 4));

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('now_playing.page_ids', [$after[4]->id]);
    }

    public function test_the_last_page_can_be_partial_and_the_playlist_stops_after_it(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(6);

        $this->event->player()->play();

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('now_playing.page_ids', $this->ids($photos, 4, 2));

        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('next_page', []);

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('player.state', 'stopped')
            ->assertJsonPath('now_playing', null);
    }

    public function test_looping_starts_the_next_page_at_the_top_after_a_partial_page(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(6);
        $this->event->player()->setLoop(true);
        $this->event->player()->playFrom($photos[4]);

        $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('next_page.0.id', $photos[0]->id);

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.page_ids', $this->ids($photos, 0, 4));
    }

    public function test_previous_goes_back_to_the_page_before(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(8);

        Carbon::setTestNow('2026-10-14 20:00:00');
        $this->event->player()->playFrom($photos[4]);

        $this->actingAs($this->admin)->postJson(route('admin.player.previous', $this->event))
            ->assertJsonPath('now_playing.id', $photos[0]->id)
            ->assertJsonPath('now_playing.page_ids', $this->ids($photos, 0, 4));

        Carbon::setTestNow('2026-10-14 20:00:02');
        $this->actingAs($this->admin)->postJson(route('admin.player.previous', $this->event))
            ->assertJsonPath('now_playing.page_ids', $this->ids($photos, 0, 4))
            ->assertJsonPath('player.elapsed_ms', 0);
    }

    public function test_previous_across_a_video_lands_on_the_video_alone(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $before = $this->photos(3);
        $video = $this->video();
        $after = $this->photos(2);

        $this->event->player()->playFrom($after[0]);

        $this->actingAs($this->admin)->postJson(route('admin.player.previous', $this->event))
            ->assertJsonPath('now_playing.page_ids', [$video->id]);

        $this->actingAs($this->admin)->postJson(route('admin.player.previous', $this->event))
            ->assertJsonPath('now_playing.page_ids', $this->ids($before, 0, 3));
    }

    public function test_previous_on_the_first_page_wraps_to_the_last_page_when_looping(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(8);
        $this->event->player()->setLoop(true);
        $this->event->player()->play();

        $this->actingAs($this->admin)->postJson(route('admin.player.previous', $this->event))
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.page_ids', $this->ids($photos, 4, 4));
    }

    public function test_every_photo_on_the_page_counts_as_played(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(6);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('counts.total', 6)
            ->assertJsonPath('counts.unplayed', 2);

        foreach ($photos as $index => $photo) {
            $this->assertSame($index < 4, $photo->fresh()->displayed_at !== null);
        }
    }

    public function test_items_approved_or_reordered_during_a_page_leave_it_alone(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        [$first, $second] = $this->photos(2);
        $pending = $this->upload('pending');

        $this->event->player()->play();
        $this->actingAs($this->admin)->post(route('admin.fotos.approve', $pending));
        $this->event->player()->reorder([$pending->id, $second->id, $first->id]);

        $feed = $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.id', $first->id);
        $this->assertSame([$first->id, $second->id], array_column($feed->json('page'), 'id'));
        $this->assertNull($pending->fresh()->displayed_at);
    }

    public function test_resuming_a_paused_page_keeps_it_as_it_was(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        [$first, $second] = $this->photos(2);

        $this->event->player()->play();
        $this->event->player()->pause();
        $this->photos(1);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.page_ids', [$first->id, $second->id]);
    }

    public function test_rejecting_a_photo_beside_the_playhead_shrinks_the_page(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(5);

        Carbon::setTestNow('2026-10-14 20:00:00');
        $this->event->player()->play();
        $revision = $this->getJson(route('vidiwall.feed', $this->event->slug))->json('revision');

        Carbon::setTestNow('2026-10-14 20:00:01.500');
        $this->actingAs($this->admin)->postJson(route('admin.fotos.reject', $photos[2]))->assertOk();

        $feed = $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('foto.id', $photos[0]->id)
            ->assertJsonPath('revision', $revision + 1)
            ->assertJsonPath('elapsed_ms', 1500);

        $this->assertSame([$photos[0]->id, $photos[1]->id, $photos[3]->id], array_column($feed->json('page'), 'id'));
    }

    public function test_rejecting_the_playhead_hands_the_page_to_the_next_photo_on_it(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(5);

        Carbon::setTestNow('2026-10-14 20:00:00');
        $this->event->player()->play();
        $revision = $this->getJson(route('vidiwall.feed', $this->event->slug))->json('revision');

        Carbon::setTestNow('2026-10-14 20:00:02');
        $this->actingAs($this->admin)->postJson(route('admin.fotos.reject', $photos[0]))->assertOk();

        $feed = $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('foto.id', $photos[1]->id)
            ->assertJsonPath('revision', $revision + 1)
            ->assertJsonPath('elapsed_ms', 2000);

        $this->assertSame($this->ids($photos, 1, 3), array_column($feed->json('page'), 'id'));

        $this->getJson(route('vidiwall.feed', ['slug' => $this->event->slug, 'done' => $photos[1]->id, 'rev' => $revision + 1]))
            ->assertJsonPath('foto.id', $photos[4]->id);
    }

    public function test_deleting_a_photo_on_the_page_takes_it_off_the_screen(): void
    {
        Storage::fake('media');
        $this->event->update(['vidiwall_photos_per_screen' => 2]);
        $photos = $this->photos(3);

        $this->event->player()->play();
        $this->actingAs($this->admin)->delete(route('admin.fotos.destroy', $photos[1]))->assertRedirect();

        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('foto.id', $photos[0]->id)
            ->assertJsonCount(1, 'page');

        $this->actingAs($this->admin)->delete(route('admin.fotos.destroy', $photos[0]))->assertRedirect();

        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('state', 'playing')
            ->assertJsonPath('foto.id', $photos[2]->id);
    }

    public function test_rejecting_the_last_photo_left_on_the_page_moves_on(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 2]);
        $photos = $this->photos(3);

        $this->event->player()->play();
        $this->actingAs($this->admin)->postJson(route('admin.fotos.reject', $photos[1]));
        $this->actingAs($this->admin)->postJson(route('admin.fotos.reject', $photos[0]));

        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('state', 'playing')
            ->assertJsonPath('foto.id', $photos[2]->id)
            ->assertJsonCount(1, 'page');
    }

    public function test_deleting_the_last_photo_of_a_looping_playlist_does_not_bring_it_back(): void
    {
        Storage::fake('media');
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(4);
        $this->event->player()->setLoop(true);
        $this->event->player()->playFrom($photos[3]);

        $this->actingAs($this->admin)->delete(route('admin.fotos.destroy', $photos[3]))->assertRedirect();

        $feed = $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.id', $photos[0]->id);
        $this->assertSame($this->ids($photos, 0, 3), array_column($feed->json('page'), 'id'));
    }

    public function test_play_next_lands_after_the_last_photo_of_the_page(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(6);

        $this->event->player()->play();

        $this->actingAs($this->admin)->postJson(route('admin.player.play-next', [$this->event, $photos[5]]))->assertOk();
        $this->assertSame([...$this->ids($photos, 0, 4), $photos[5]->id, $photos[4]->id], $this->playlistIds());

        $this->actingAs($this->admin)->postJson(route('admin.player.play-next', [$this->event, $photos[2]]))->assertOk();
        $this->assertSame([...$this->ids($photos, 0, 4), $photos[5]->id, $photos[4]->id], $this->playlistIds());

        $this->actingAs($this->admin)->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('now_playing.page_ids', [$photos[5]->id, $photos[4]->id]);
    }

    public function test_push_live_plays_a_page_from_the_item_right_after_the_current_page(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 2]);
        $photos = $this->photos(5);

        $this->event->player()->play();
        $photos[4]->pushToScreen();

        $this->assertSame([$photos[0]->id, $photos[1]->id, $photos[4]->id, $photos[2]->id, $photos[3]->id], $this->playlistIds());

        $feed = $this->getJson(route('vidiwall.feed', $this->event->slug))->assertJsonPath('foto.id', $photos[4]->id);
        $this->assertSame([$photos[4]->id, $photos[2]->id], array_column($feed->json('page'), 'id'));
    }

    public function test_switching_to_four_per_screen_while_playing_rebuilds_the_page_and_keeps_its_time(): void
    {
        $photos = $this->photos(5);

        Carbon::setTestNow('2026-10-14 20:00:00');
        $this->event->player()->play();
        $revision = $this->event->player()->revision;

        Carbon::setTestNow('2026-10-14 20:00:02');
        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photos-per-screen', $this->event), ['count' => 4])
            ->assertJsonPath('player.revision', $revision + 1)
            ->assertJsonPath('player.elapsed_ms', 2000)
            ->assertJsonPath('now_playing.id', $photos[0]->id)
            ->assertJsonPath('now_playing.page_ids', $this->ids($photos, 0, 4))
            ->assertJsonPath('counts.unplayed', 1);

        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('layout', 4)
            ->assertJsonPath('elapsed_ms', 2000)
            ->assertJsonCount(4, 'page');

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photos-per-screen', $this->event), ['count' => 1])
            ->assertJsonPath('player.revision', $revision + 2)
            ->assertJsonPath('now_playing.page_ids', [$photos[0]->id]);
    }

    public function test_photos_per_screen_unchanged_leaves_the_revision_alone(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 2]);
        $this->photos(3);
        $this->event->player()->play();
        $revision = $this->event->player()->revision;

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photos-per-screen', $this->event), ['count' => 2])
            ->assertOk()
            ->assertJsonPath('player.revision', $revision);
    }

    public function test_photos_per_screen_change_refreshes_the_playlist_version(): void
    {
        $this->upload();
        $version = $this->actingAs($this->admin)->getJson(route('admin.player.status', $this->event))->json('version');

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.photos-per-screen', $this->event), ['count' => 4, 'v' => $version])
            ->assertOk()
            ->assertJsonCount(1, 'items');

        $this->assertNotSame($version, $this->actingAs($this->admin)->getJson(route('admin.player.status', $this->event))->json('version'));
    }

    public function test_status_estimates_how_long_one_run_takes(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4, 'vidiwall_photo_seconds' => 5]);
        $this->photos(5);
        $this->video(6);
        $this->photos(4);

        $this->actingAs($this->admin)
            ->getJson(route('admin.player.status', $this->event))
            ->assertJsonPath('run_ms', 2 * 5000 + 6000 + 5000);
    }

    public function test_a_player_cued_before_pages_existed_still_plays_and_moves_on(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);
        $photos = $this->photos(6);

        Carbon::setTestNow('2026-10-14 20:00:00');
        $player = $this->event->player();
        $player->forceFill([
            'state' => VidiwallPlayer::STATE_PLAYING,
            'foto_upload_id' => $photos[0]->id,
            'page_ids' => null,
            'started_ms' => now()->getTimestampMs(),
            'revision' => 7,
        ])->save();

        $this->getJson(route('vidiwall.feed', $this->event->slug))
            ->assertJsonPath('foto.id', $photos[0]->id)
            ->assertJsonCount(1, 'page')
            ->assertJsonPath('next_page.0.id', $photos[1]->id);

        $feed = $this->getJson(route('vidiwall.feed', ['slug' => $this->event->slug, 'done' => $photos[0]->id, 'rev' => 7]))
            ->assertJsonPath('foto.id', $photos[1]->id);

        $this->assertSame($this->ids($photos, 1, 4), array_column($feed->json('page'), 'id'));
    }

    public function test_player_page_shows_the_photos_on_screen_control(): void
    {
        $this->event->update(['vidiwall_photos_per_screen' => 4]);

        $this->actingAs($this->admin)
            ->get(route('admin.player.index', $this->event))
            ->assertOk()
            ->assertSee('Photos on screen')
            ->assertSee('data-per-screen="4" aria-pressed="true"', false)
            ->assertSee('data-per-screen="1" aria-pressed="false"', false)
            ->assertSee('s per page, videos play through')
            ->assertSee(json_encode(route('admin.player.photos-per-screen', $this->event)), false);
    }

    /**
     * Approved photos added to the end of the playlist.
     *
     * @return list<FotoUpload>
     */
    private function photos(int $count): array
    {
        return array_map(fn () => $this->upload(), range(1, $count));
    }

    private function video(float $seconds = 5): FotoUpload
    {
        return $this->upload('approved', null, ['media_type' => 'video', 'video_path' => 'fotos/clip.mp4', 'video_duration' => $seconds]);
    }

    /**
     * @param  list<FotoUpload>  $fotos
     * @return list<int>
     */
    private function ids(array $fotos, int $offset, int $length): array
    {
        return array_map(fn (FotoUpload $foto) => $foto->id, array_slice($fotos, $offset, $length));
    }

    /**
     * @return list<int>
     */
    private function playlistIds(): array
    {
        return $this->event->playlist()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Approved uploads join the end of the playlist, as approving them would.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function upload(string $status = 'approved', ?Event $event = null, array $overrides = []): FotoUpload
    {
        $event ??= $this->event;
        $position = $status === 'approved'
            ? (int) FotoUpload::query()->where('event_id', $event->id)->where('status', 'approved')->max('display_order') + 1
            : 0;

        return FotoUpload::query()->create(array_merge([
            'event_id' => $event->id,
            'file_path' => 'fotos/event-'.$event->id.'/'.uniqid('upload-', true).'.jpg',
            'media_type' => 'photo',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploader_name' => 'Guest',
            'status' => $status,
            'display_order' => $position,
            'approved_at' => $status === 'approved' ? now() : null,
        ], $overrides));
    }
}
