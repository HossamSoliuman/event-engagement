<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FanClashMatchup;
use App\Models\FanClashRound;
use App\Models\FotoUpload;
use App\Models\QuizQuestion;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaCleanupTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
    }

    public function test_deleting_an_event_removes_every_file_it_owns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->eventWithMedia();
        $otherEvent = $this->eventWithMedia();

        $owned = $event->ownedMediaPaths();
        $kept = $otherEvent->ownedMediaPaths();

        $this->assertCount(15, $owned);
        Storage::disk('media')->assertExists($owned->all());

        $this->actingAs($admin)
            ->delete(route('admin.events.destroy', $event))
            ->assertRedirect(route('admin.events.index'));

        Storage::disk('media')->assertMissing($owned->all());
        Storage::disk('media')->assertMissing("fotos/event-{$event->id}/stray-thumb.jpg");
        Storage::disk('media')->assertExists($kept->all());
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_prune_deletes_only_unreferenced_files(): void
    {
        $event = $this->eventWithMedia();
        $user = User::factory()->create(['avatar_path' => 'avatars/me.png']);
        SiteSetting::set('privacy_policy_path', 'legal/privacy-policy.pdf');

        $disk = Storage::disk('media');
        $disk->put('avatars/me.png', 'x');
        $disk->put('legal/privacy-policy.pdf', 'x');
        $disk->put('logos/orphan.png', 'x');
        $disk->put('fotos/event-999/orphan.jpg', 'x');
        $disk->put('.gitignore', '*');

        $this->artisan('media:prune', ['--min-age' => 0])
            ->expectsOutputToContain('Deleted 3 orphan(s)')
            ->assertSuccessful();

        $disk->assertMissing(['logos/orphan.png', 'fotos/event-999/orphan.jpg']);
        $disk->assertMissing("fotos/event-{$event->id}/stray-thumb.jpg");
        $disk->assertExists($event->ownedMediaPaths()->all());
        $disk->assertExists(['avatars/me.png', 'legal/privacy-policy.pdf', '.gitignore']);
        $this->assertSame('avatars/me.png', $user->fresh()->avatar_path);
    }

    public function test_prune_keeps_media_of_soft_deleted_events(): void
    {
        $event = $this->eventWithMedia();
        $event->delete();

        $this->artisan('media:prune', ['--min-age' => 0])
            ->expectsOutputToContain('Deleted 1 orphan(s)')
            ->assertSuccessful();

        Storage::disk('media')->assertExists($event->ownedMediaPaths()->all());
    }

    public function test_prune_dry_run_deletes_nothing(): void
    {
        Storage::disk('media')->put('logos/orphan.png', 'x');

        $this->artisan('media:prune', ['--dry-run' => true, '--min-age' => 0])
            ->expectsOutputToContain('Would delete 1 orphan(s)')
            ->assertSuccessful();

        Storage::disk('media')->assertExists('logos/orphan.png');
    }

    public function test_prune_skips_recently_modified_orphans(): void
    {
        Storage::disk('media')->put('fotos/event-999/in-flight.jpg', 'x');

        $this->artisan('media:prune')
            ->expectsOutputToContain('skipped 1 recent file(s)')
            ->assertSuccessful();

        Storage::disk('media')->assertExists('fotos/event-999/in-flight.jpg');
    }

    /**
     * An event whose own columns, JSON configs, uploads, quiz and fan clash
     * rows all point at real files on the fake media disk, plus one stray
     * thumbnail in its upload directory that no row references.
     */
    private function eventWithMedia(): Event
    {
        $event = Event::factory()->create();
        $id = $event->id;
        $disk = Storage::disk('media');

        $event->update([
            'qr_code_path' => "qrcodes/event-{$id}.svg",
            'logo_path' => "logos/{$id}-logo.png",
            'sponsor_logo_path' => "logos/{$id}-sponsor.png",
            'background_image_path' => "backgrounds/{$id}.jpg",
            'quiz_end_sponsor_logo_path' => "quiz-sponsors/{$id}-end.png",
            'vidiwall_frame_config' => ['enabled' => true, 'logo_path' => "frames/{$id}.png"],
            'tile_fotobomb_config' => ['image_path' => "tiles/fotobomb/{$id}.jpg"],
        ]);

        FotoUpload::create([
            'event_id' => $id,
            'file_path' => "fotos/event-{$id}/photo.jpg",
            'thumbnail_path' => "fotos/event-{$id}/thumb_photo.jpg",
            'media_type' => 'photo',
            'status' => 'approved',
        ]);
        FotoUpload::create([
            'event_id' => $id,
            'file_path' => "fotos/event-{$id}/clip.mp4",
            'video_path' => "fotos/event-{$id}/clip.mp4",
            'media_type' => 'video',
            'status' => 'pending',
        ]);
        QuizQuestion::create([
            'event_id' => $id,
            'question_text' => 'Who wins?',
            'options' => ['A', 'B'],
            'correct_option' => 0,
            'sponsor_logo_path' => "quiz-sponsors/{$id}-q.png",
        ]);
        $matchup = FanClashMatchup::create([
            'event_id' => $id,
            'side_a_name' => 'A',
            'side_b_name' => 'B',
            'side_a_color' => '#111111',
            'side_b_color' => '#222222',
            'side_a_image_path' => "fanclash/{$id}-a.png",
            'side_b_image_path' => "fanclash/{$id}-b.png",
            'sponsor_logo_path' => "fanclash/{$id}-sponsor.png",
        ]);
        FanClashRound::create([
            'event_id' => $id,
            'fan_clash_matchup_id' => $matchup->id,
            'side_a_name' => 'A',
            'side_b_name' => 'B',
            'side_a_color' => '#111111',
            'side_b_color' => '#222222',
            'side_a_image_path' => $matchup->side_a_image_path,
            'side_b_image_path' => "fanclash/{$id}-round-b.png",
            'status' => 'finished',
            'duration_seconds' => 30,
        ]);

        foreach ($event->ownedMediaPaths() as $path) {
            $disk->put($path, 'x');
        }
        $disk->put("fotos/event-{$id}/stray-thumb.jpg", 'x');

        return $event->fresh();
    }
}
