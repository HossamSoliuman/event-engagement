<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VidiwallQueueTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_feed_is_idle_when_nothing_is_approved(): void
    {
        $event = Event::factory()->create();
        $this->upload($event, 'pending');

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertOk()
            ->assertJson(['mode' => 'player', 'state' => 'stopped', 'foto' => null]);
    }

    public function test_approving_adds_the_item_to_the_playlist_without_playing_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $this->upload($event, 'approved');
        $foto = $this->upload($event, 'pending');

        $this->actingAs($admin)->post(route('admin.fotos.approve', $foto))->assertRedirect();

        $foto->refresh();
        $this->assertTrue($foto->isQueuedForScreen());
        $this->assertSame(2, $foto->display_order);
        $this->assertSame(2, $event->getQueuedFotosCount());

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('state', 'stopped')
            ->assertJsonPath('foto', null);

        $this->assertNull($foto->fresh()->displayed_at);
    }

    public function test_playlist_plays_in_approval_order_once_pressed_then_stops_at_the_end(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $second = $this->upload($event, 'pending');
        $first = $this->upload($event, 'pending');

        $this->actingAs($admin)->post(route('admin.fotos.approve', $first));
        $this->actingAs($admin)->post(route('admin.fotos.approve', $second));
        $this->actingAs($admin)->postJson(route('admin.player.play', $event))->assertOk();

        $response = $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('state', 'playing')
            ->assertJsonPath('foto.id', $first->id)
            ->assertJsonPath('foto.slot_ms', 4000);

        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $first->id);

        $response = $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $first->id, 'rev' => $response->json('revision')]))
            ->assertJsonPath('foto.id', $second->id);

        $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $second->id, 'rev' => $response->json('revision')]))
            ->assertJsonPath('state', 'stopped')
            ->assertJsonPath('foto', null);

        $this->assertNotNull($first->fresh()->displayed_at);
        $this->assertNotNull($second->fresh()->displayed_at);
        $this->assertSame(0, $event->getQueuedFotosCount());
    }

    public function test_server_advances_an_unacknowledged_photo_when_its_slot_ends(): void
    {
        $event = Event::factory()->create();
        $first = $this->upload($event, 'approved');
        $second = $this->upload($event, 'approved');

        Carbon::setTestNow('2026-09-18 12:00:00');
        $event->player()->play();
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $first->id);

        Carbon::setTestNow('2026-09-18 12:00:03.900');
        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('foto.id', $first->id)
            ->assertJsonPath('elapsed_ms', 3900);

        Carbon::setTestNow('2026-09-18 12:00:04');
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $second->id);
    }

    public function test_server_gives_an_unacknowledged_video_a_grace_period(): void
    {
        $event = Event::factory()->create();
        $video = $this->upload($event, 'approved', ['media_type' => 'video', 'video_path' => 'fotos/clip.mp4', 'video_duration' => 8]);
        $photo = $this->upload($event, 'approved');

        Carbon::setTestNow('2026-09-18 12:00:00');
        $event->player()->play();

        Carbon::setTestNow('2026-09-18 12:00:12');
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $video->id);

        Carbon::setTestNow('2026-09-18 12:00:13');
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $photo->id);
    }

    public function test_push_live_plays_the_item_now_then_the_playlist_carries_on(): void
    {
        $event = Event::factory()->create();
        $alreadyShown = $this->upload($event, 'approved', ['displayed_at' => now()->subMinutes(8)]);
        $queuedA = $this->upload($event, 'approved');
        $queuedB = $this->upload($event, 'approved');

        $event->player()->playFrom($queuedA);
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $queuedA->id);

        $alreadyShown->pushToScreen();

        $response = $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $alreadyShown->id);

        $response = $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $alreadyShown->id, 'rev' => $response->json('revision')]))
            ->assertJsonPath('foto.id', $queuedB->id);

        $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $queuedB->id, 'rev' => $response->json('revision')]))
            ->assertJsonPath('foto', null);

        $this->assertSame(
            [$queuedA->id, $alreadyShown->id, $queuedB->id],
            $event->playlist()->pluck('id')->all(),
        );
    }

    public function test_push_live_starts_playback_when_the_player_is_stopped(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $this->upload($event, 'approved');
        $pending = $this->upload($event, 'pending');

        $pending->approve($admin->id);
        $pending->pushToScreen();

        $this->assertTrue($pending->fresh()->isApproved());
        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('state', 'playing')
            ->assertJsonPath('foto.id', $pending->id);
    }

    public function test_stale_done_acknowledgement_does_not_clear_a_newly_pushed_item(): void
    {
        $event = Event::factory()->create();
        $pushed = $this->upload($event, 'approved', ['displayed_at' => now()->subMinutes(8)]);
        $queued = $this->upload($event, 'approved');

        $event->player()->playFrom($queued);
        $revision = $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $queued->id)->json('revision');
        $pushed->pushToScreen();

        $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $queued->id, 'rev' => $revision]))
            ->assertJsonPath('foto.id', $pushed->id);
    }

    public function test_done_acknowledgement_for_an_older_revision_of_the_same_item_is_ignored(): void
    {
        $event = Event::factory()->create();
        $first = $this->upload($event, 'approved');
        $this->upload($event, 'approved');

        $event->player()->play();
        $staleRevision = $this->getJson(route('vidiwall.feed', $event->slug))->json('revision');
        $event->player()->playFrom($first);

        $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $first->id, 'rev' => $staleRevision]))
            ->assertJsonPath('foto.id', $first->id);
    }

    public function test_skipping_the_live_item_advances_the_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $first = $this->upload($event, 'approved');
        $second = $this->upload($event, 'approved');

        $event->player()->play();
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $first->id);

        $this->actingAs($admin)->post(route('admin.fotos.remove-from-screen', $first))->assertRedirect();

        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $second->id);
    }

    public function test_rejecting_removes_from_queue_and_restoring_requeues(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $foto = $this->upload($event, 'approved', ['displayed_at' => now()->subMinutes(4)]);
        $other = $this->upload($event, 'approved');

        $event->player()->playFrom($foto);

        $this->actingAs($admin)->post(route('admin.fotos.reject', $foto));
        $this->assertSame([$other->id], $event->playlist()->pluck('id')->all());
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $other->id);

        $this->actingAs($admin)->post(route('admin.fotos.approve', $foto));
        $this->assertTrue($foto->fresh()->isQueuedForScreen());
        $this->assertSame([$other->id, $foto->id], $event->playlist()->pluck('id')->all());
    }

    public function test_rejecting_the_last_live_item_stops_the_player(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $foto = $this->upload($event, 'approved');

        $event->player()->play();
        $this->actingAs($admin)->post(route('admin.fotos.reject', $foto));

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('state', 'stopped')
            ->assertJsonPath('foto', null);
    }

    public function test_video_slot_lasts_for_the_clip_duration(): void
    {
        $event = Event::factory()->create();
        $video = $this->upload($event, 'approved', [
            'media_type' => 'video',
            'video_path' => 'fotos/clip.mp4',
            'video_duration' => 8.5,
        ]);

        $event->player()->play();

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('foto.id', $video->id)
            ->assertJsonPath('foto.slot_ms', 8500);

        $this->assertSame(8.5, $video->fresh()->screenSeconds());
    }

    public function test_photo_slot_follows_the_event_autoplay_duration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create(['vidiwall_photo_seconds' => 7]);
        $foto = $this->upload($event, 'approved');

        $event->player()->play();

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('foto.id', $foto->id)
            ->assertJsonPath('foto.slot_ms', 7000);

        $this->actingAs($admin)
            ->getJson(route('admin.fotos.status', [$event, 'ids' => $foto->id]))
            ->assertJsonPath('live.slot_ms', 7000);
    }

    public function test_server_expiry_uses_the_event_autoplay_duration(): void
    {
        $event = Event::factory()->create(['vidiwall_photo_seconds' => 10]);
        $first = $this->upload($event, 'approved');
        $second = $this->upload($event, 'approved');

        Carbon::setTestNow('2026-09-18 12:00:00');
        $event->player()->play();
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $first->id);

        Carbon::setTestNow('2026-09-18 12:00:09');
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $first->id);

        Carbon::setTestNow('2026-09-18 12:00:10');
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $second->id);
    }

    public function test_short_video_plays_for_its_own_length_not_the_photo_duration(): void
    {
        $event = Event::factory()->create(['vidiwall_photo_seconds' => 6]);
        $this->upload($event, 'approved', [
            'media_type' => 'video',
            'video_path' => 'fotos/clip.mp4',
            'video_duration' => 2.5,
        ]);

        $event->player()->play();

        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.slot_ms', 2500);
    }

    public function test_admin_can_set_the_autoplay_duration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.events.update', $event), $this->eventPayload($event, ['vidiwall_photo_seconds' => 12]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(12, $event->fresh()->vidiwall_photo_seconds);

        $this->actingAs($admin)
            ->get(route('admin.player.index', $event))
            ->assertOk()
            ->assertSee('<span id="vpSecondsSummary">12</span>s per photo', false)
            ->assertSee('inputmode="numeric" value="12"', false);
    }

    public function test_autoplay_duration_is_left_unchanged_when_not_submitted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create(['vidiwall_photo_seconds' => 9]);

        $this->actingAs($admin)
            ->put(route('admin.events.update', $event), $this->eventPayload($event))
            ->assertSessionHasNoErrors();

        $this->assertSame(9, $event->fresh()->vidiwall_photo_seconds);
    }

    public function test_autoplay_duration_must_be_a_whole_number_between_1_and_60(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create(['vidiwall_photo_seconds' => 5]);

        foreach (['', 0, 61, 2.5, 'abc'] as $invalid) {
            $this->actingAs($admin)
                ->put(route('admin.events.update', $event), $this->eventPayload($event, ['vidiwall_photo_seconds' => $invalid]))
                ->assertSessionHasErrors('vidiwall_photo_seconds');
        }

        $this->assertSame(5, $event->fresh()->vidiwall_photo_seconds);
    }

    public function test_new_events_default_to_four_seconds(): void
    {
        $event = Event::factory()->create();

        $this->assertSame(FotoUpload::SCREEN_SECONDS, $event->fresh()->vidiwall_photo_seconds);
    }

    public function test_moderation_page_shows_queue_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $this->upload($event, 'approved');
        $this->upload($event, 'approved', ['displayed_at' => now()->subMinutes(8)]);

        $this->actingAs($admin)
            ->get(route('admin.fotos.index', [$event, 'status' => 'approved']))
            ->assertOk()
            ->assertSee('Queue: 1')
            ->assertSee('In queue')
            ->assertSee('Shown')
            ->assertSee('data-act="reject"', false)
            ->assertSee('data-act="delete"', false)
            ->assertDontSee('Push Live')
            ->assertDontSee('nothing plays until you press Play')
            ->assertSee(route('admin.player.index', $event));
    }

    public function test_moderator_page_marks_the_item_on_screen(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event = Event::factory()->create();
        $event->moderators()->attach($moderator);
        $this->upload($event, 'approved');
        $this->upload($event, 'approved');

        $event->player()->play();

        $this->actingAs($moderator)
            ->get(route('moderator.fotos.index', [$event, 'status' => 'approved']))
            ->assertOk()
            ->assertSee('On screen')
            ->assertSee('In queue')
            ->assertDontSee('Push Live');
    }

    public function test_admin_status_endpoint_reports_live_item_queue_and_card_states(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $shown = $this->upload($event, 'approved', ['displayed_at' => now()->subMinutes(8)]);
        $live = $this->upload($event, 'approved');
        $queued = $this->upload($event, 'approved');
        $pending = $this->upload($event, 'pending');

        $event->player()->playFrom($live);

        $this->actingAs($admin)
            ->getJson(route('admin.fotos.status', [$event, 'ids' => implode(',', [$live->id, $queued->id, $shown->id, $pending->id])]))
            ->assertOk()
            ->assertJsonPath('live.id', $live->id)
            ->assertJsonPath('live.slot_ms', 4000)
            ->assertJsonPath('live.is_video', false)
            ->assertJsonPath('live.state', 'playing')
            ->assertJsonPath('player_state', 'playing')
            ->assertJsonPath('queued', 1)
            ->assertJsonPath('counts.approved', 3)
            ->assertJsonPath('counts.pending', 1)
            ->assertJsonPath('counts.rejected', 0)
            ->assertJsonCount(4, 'items')
            ->assertJsonFragment(['id' => $live->id, 'state' => 'live'])
            ->assertJsonFragment(['id' => $queued->id, 'state' => 'queued', 'shown_human' => null])
            ->assertJsonFragment(['id' => $shown->id, 'state' => 'shown'])
            ->assertJsonFragment(['id' => $pending->id, 'state' => 'pending']);
    }

    public function test_status_endpoint_reports_a_paused_item_as_live(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $foto = $this->upload($event, 'approved');

        $event->player()->play();
        $event->player()->pause();

        $this->actingAs($admin)
            ->getJson(route('admin.fotos.status', [$event, 'ids' => $foto->id]))
            ->assertJsonPath('live.id', $foto->id)
            ->assertJsonPath('live.state', 'paused')
            ->assertJsonFragment(['id' => $foto->id, 'state' => 'live']);
    }

    public function test_status_endpoint_is_idle_without_ids_and_requires_an_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();

        $this->getJson(route('admin.fotos.status', $event))->assertUnauthorized();

        $this->actingAs($admin)
            ->getJson(route('admin.fotos.status', $event))
            ->assertOk()
            ->assertJson(['live' => null, 'queued' => 0, 'items' => []]);
    }

    public function test_moderator_status_endpoint_is_scoped_to_assigned_events(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event = Event::factory()->create();
        $other = Event::factory()->create();
        $event->moderators()->attach($moderator);
        $foto = $this->upload($event, 'approved');

        $this->actingAs($moderator)
            ->getJson(route('moderator.fotos.status', [$event, 'ids' => $foto->id]))
            ->assertOk()
            ->assertJsonPath('queued', 1)
            ->assertJsonFragment(['id' => $foto->id, 'state' => 'queued']);

        $this->actingAs($moderator)
            ->getJson(route('moderator.fotos.status', $other))
            ->assertForbidden();
    }

    public function test_moderation_marks_every_photo_on_the_page_as_on_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create(['vidiwall_photos_per_screen' => 4]);
        $onScreen = [$this->upload($event, 'approved'), $this->upload($event, 'approved'), $this->upload($event, 'approved')];
        $video = $this->upload($event, 'approved', ['media_type' => 'video', 'video_path' => 'fotos/clip.mp4', 'video_duration' => 4]);

        $event->player()->play();

        $response = $this->actingAs($admin)
            ->getJson(route('admin.fotos.status', [$event, 'ids' => implode(',', [...array_column($onScreen, 'id'), $video->id])]))
            ->assertJsonPath('live.id', $onScreen[0]->id)
            ->assertJsonPath('queued', 1)
            ->assertJsonFragment(['id' => $video->id, 'state' => 'queued', 'shown_human' => null]);

        foreach ($onScreen as $foto) {
            $response->assertJsonFragment(['id' => $foto->id, 'state' => 'live']);
        }

        $csv = $this->actingAs($admin)->get(route('admin.fotos.export', $event))->assertOk()->getContent();
        $this->assertSame(3, substr_count($csv, ',approved,Yes,'));
        $this->assertSame(1, substr_count($csv, ',approved,No,'));
    }

    public function test_skipping_a_photo_beside_the_playhead_keeps_the_rest_of_the_page_up(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create(['vidiwall_photos_per_screen' => 2]);
        $first = $this->upload($event, 'approved');
        $second = $this->upload($event, 'approved');
        $third = $this->upload($event, 'approved');

        $event->player()->play();
        $this->actingAs($admin)->post(route('admin.fotos.remove-from-screen', $second))->assertRedirect();

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('foto.id', $first->id)
            ->assertJsonCount(1, 'page');

        $this->assertTrue($second->fresh()->isApproved());
        $this->assertSame([$first->id, $second->id, $third->id], $event->playlist()->pluck('id')->all());

        $this->actingAs($admin)->post(route('admin.fotos.remove-from-screen', $third))->assertRedirect();
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $first->id)->assertJsonCount(1, 'page');
    }

    public function test_feed_preloads_the_whole_next_page_while_stopped(): void
    {
        $event = Event::factory()->create(['vidiwall_photos_per_screen' => 2]);
        $first = $this->upload($event, 'approved');
        $second = $this->upload($event, 'approved');
        $this->upload($event, 'approved');

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('state', 'stopped')
            ->assertJsonPath('page', [])
            ->assertJsonPath('next.id', $first->id)
            ->assertJsonPath('next_page.1.id', $second->id)
            ->assertJsonCount(2, 'next_page');

        $this->assertNull($event->vidiwallPlayer()->first());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function eventPayload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'name' => $event->name,
            'is_active' => 1,
            'design' => Event::landingDesignDefaults(),
        ], $overrides);
    }

    /**
     * Approved uploads join the end of the playlist, as approving them would.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function upload(Event $event, string $status, array $overrides = []): FotoUpload
    {
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
