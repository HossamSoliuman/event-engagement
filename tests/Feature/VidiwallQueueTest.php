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
            ->assertJson(['mode' => 'queue', 'foto' => null, 'queued' => 0]);
    }

    public function test_approving_queues_the_item_and_the_feed_starts_playing_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $foto = $this->upload($event, 'pending');

        $this->actingAs($admin)->post(route('admin.fotos.approve', $foto))->assertRedirect();

        $this->assertTrue($foto->fresh()->isQueuedForScreen());
        $this->assertSame(1, $event->getQueuedFotosCount());

        $response = $this->getJson(route('vidiwall.feed', $event->slug))->assertOk();

        $response->assertJsonPath('foto.id', $foto->id)
            ->assertJsonPath('foto.slot_ms', 4000)
            ->assertJsonPath('queued', 0);

        $foto->refresh();
        $this->assertTrue($foto->on_screen);
        $this->assertNotNull($foto->displayed_at);
    }

    public function test_queue_plays_in_approval_order_once_each_then_returns_to_idle(): void
    {
        $event = Event::factory()->create();
        $second = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(2)]);
        $first = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(5)]);

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('foto.id', $first->id)
            ->assertJsonPath('queued', 1);

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('foto.id', $first->id);

        $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $first->id]))
            ->assertJsonPath('foto.id', $second->id)
            ->assertJsonPath('queued', 0);

        $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $second->id]))
            ->assertJsonPath('foto', null);

        $this->assertFalse($first->fresh()->on_screen);
        $this->assertFalse($second->fresh()->on_screen);
        $this->assertNotNull($first->fresh()->displayed_at);
        $this->assertNotNull($second->fresh()->displayed_at);
    }

    public function test_server_expires_an_unacknowledged_item_after_its_slot_plus_grace(): void
    {
        $event = Event::factory()->create();
        $first = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(5)]);
        $second = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(2)]);

        Carbon::setTestNow('2026-09-18 12:00:00');
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $first->id);

        Carbon::setTestNow('2026-09-18 12:00:08');
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $first->id);

        Carbon::setTestNow('2026-09-18 12:00:10');
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $second->id);
    }

    public function test_push_live_jumps_the_queue_then_the_queue_resumes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $queuedA = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(5)]);
        $queuedB = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(4)]);
        $alreadyShown = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(9), 'displayed_at' => now()->subMinutes(8)]);

        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $queuedA->id);

        $this->actingAs($admin)->post(route('admin.fotos.push-to-screen', $alreadyShown))->assertRedirect();

        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $alreadyShown->id);

        $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $alreadyShown->id]))
            ->assertJsonPath('foto.id', $queuedB->id);

        $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $queuedB->id]))
            ->assertJsonPath('foto', null);
    }

    public function test_stale_done_acknowledgement_does_not_clear_a_newly_pushed_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $queued = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(5)]);
        $pushed = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(9), 'displayed_at' => now()->subMinutes(8)]);

        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $queued->id);
        $this->actingAs($admin)->post(route('admin.fotos.push-to-screen', $pushed));

        $this->getJson(route('vidiwall.feed', ['slug' => $event->slug, 'done' => $queued->id]))
            ->assertJsonPath('foto.id', $pushed->id);
    }

    public function test_skipping_the_live_item_advances_the_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $first = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(5)]);
        $second = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(4)]);

        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $first->id);

        $this->actingAs($admin)->post(route('admin.fotos.remove-from-screen', $first))->assertRedirect();

        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $second->id);
    }

    public function test_rejecting_removes_from_queue_and_restoring_requeues(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $foto = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(5), 'displayed_at' => now()->subMinutes(4)]);

        $this->actingAs($admin)->post(route('admin.fotos.reject', $foto));
        $this->assertSame(0, $event->getQueuedFotosCount());
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto', null);

        $this->actingAs($admin)->post(route('admin.fotos.approve', $foto));
        $this->assertTrue($foto->fresh()->isQueuedForScreen());
        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $foto->id);
    }

    public function test_video_slot_lasts_for_the_clip_duration(): void
    {
        $event = Event::factory()->create();
        $video = $this->upload($event, 'approved', [
            'media_type' => 'video',
            'video_path' => 'fotos/clip.mp4',
            'video_duration' => 8.5,
            'approved_at' => now()->subMinute(),
        ]);

        $this->getJson(route('vidiwall.feed', $event->slug))
            ->assertJsonPath('foto.id', $video->id)
            ->assertJsonPath('foto.slot_ms', 8500);

        $this->assertSame(8.5, $video->fresh()->screenSeconds());
    }

    public function test_moderation_page_shows_queue_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(5)]);
        $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(9), 'displayed_at' => now()->subMinutes(8)]);

        $this->actingAs($admin)
            ->get(route('admin.fotos.index', [$event, 'status' => 'approved']))
            ->assertOk()
            ->assertSee('Queue: 1')
            ->assertSee('IN QUEUE')
            ->assertSee('SHOWN');
    }

    public function test_moderator_page_shows_queue_state_and_skip_for_live_item(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event = Event::factory()->create();
        $event->moderators()->attach($moderator);
        $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(5)]);
        $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(4)]);

        $this->getJson(route('vidiwall.feed', $event->slug))->assertOk();

        $this->actingAs($moderator)
            ->get(route('moderator.fotos.index', [$event, 'status' => 'approved']))
            ->assertOk()
            ->assertSee('Currently on Vidiwall')
            ->assertSee('Skip')
            ->assertSee('In Queue')
            ->assertSee('1</strong> waiting in the queue', false);
    }

    public function test_admin_status_endpoint_reports_live_item_queue_and_card_states(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $live = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(5)]);
        $queued = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(4)]);
        $shown = $this->upload($event, 'approved', ['approved_at' => now()->subMinutes(9), 'displayed_at' => now()->subMinutes(8)]);
        $pending = $this->upload($event, 'pending');

        $this->getJson(route('vidiwall.feed', $event->slug))->assertJsonPath('foto.id', $live->id);

        $this->actingAs($admin)
            ->getJson(route('admin.fotos.status', [$event, 'ids' => implode(',', [$live->id, $queued->id, $shown->id, $pending->id])]))
            ->assertOk()
            ->assertJsonPath('live.id', $live->id)
            ->assertJsonPath('live.slot_ms', 4000)
            ->assertJsonPath('live.is_video', false)
            ->assertJsonPath('queued', 1)
            ->assertJsonPath('counts.approved', 3)
            ->assertJsonPath('counts.pending', 1)
            ->assertJsonCount(4, 'items')
            ->assertJsonFragment(['id' => $live->id, 'state' => 'live'])
            ->assertJsonFragment(['id' => $queued->id, 'state' => 'queued', 'shown_human' => null])
            ->assertJsonFragment(['id' => $shown->id, 'state' => 'shown'])
            ->assertJsonFragment(['id' => $pending->id, 'state' => 'pending']);
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
        $foto = $this->upload($event, 'approved', ['approved_at' => now()->subMinute()]);

        $this->actingAs($moderator)
            ->getJson(route('moderator.fotos.status', [$event, 'ids' => $foto->id]))
            ->assertOk()
            ->assertJsonPath('queued', 1)
            ->assertJsonFragment(['id' => $foto->id, 'state' => 'queued']);

        $this->actingAs($moderator)
            ->getJson(route('moderator.fotos.status', $other))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function upload(Event $event, string $status, array $overrides = []): FotoUpload
    {
        return FotoUpload::query()->create(array_merge([
            'event_id' => $event->id,
            'file_path' => 'fotos/event-'.$event->id.'/'.uniqid('upload-', true).'.jpg',
            'media_type' => 'photo',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploader_name' => 'Guest',
            'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
        ], $overrides));
    }
}
