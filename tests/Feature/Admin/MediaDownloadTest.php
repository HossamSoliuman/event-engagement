<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaDownloadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_open_the_platform_media_download_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create(['name' => 'Summer Launch']);
        $this->createUpload($event, 'photo', 'approved', 'guest-photo.jpg');
        $this->createUpload($event, 'video', 'pending', 'guest-video.mp4');

        $this->actingAs($admin)
            ->get(route('admin.media-downloads.index'))
            ->assertOk()
            ->assertSee('Media Downloads')
            ->assertSee('Summer Launch')
            ->assertSee('Summer Launch — 2 uploads');
    }

    public function test_moderator_cannot_access_platform_wide_media_downloads(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($moderator)
            ->get(route('admin.media-downloads.index'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->post(route('admin.media-downloads.download'), [
                'media_type' => 'all',
                'status' => 'all',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_download_only_images_from_a_specific_event(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $selectedEvent = Event::factory()->create(['name' => 'Selected Event', 'slug' => 'selected-event']);
        $otherEvent = Event::factory()->create(['name' => 'Other Event', 'slug' => 'other-event']);

        $photo = $this->createUpload($selectedEvent, 'photo', 'approved', 'My Guest Photo.jpg');
        $video = $this->createUpload($selectedEvent, 'video', 'approved', 'clip.mp4');
        $otherPhoto = $this->createUpload($otherEvent, 'photo', 'approved', 'other.jpg');

        Storage::disk('public')->put($photo->file_path, 'photo-content');
        Storage::disk('public')->put($video->file_path, 'video-content');
        Storage::disk('public')->put($otherPhoto->file_path, 'other-photo-content');

        $response = $this->actingAs($admin)
            ->post(route('admin.media-downloads.download'), [
                'event_id' => $selectedEvent->id,
                'media_type' => 'photo',
                'status' => 'all',
                'include_manifest' => '1',
            ]);

        $response->assertOk()->assertDownload();

        $archivePath = $response->baseResponse->getFile()->getPathname();
        $archive = new \PharData($archivePath);
        $photoPath = 'selected-event/images/'.str_pad((string) $photo->id, 6, '0', STR_PAD_LEFT).'-My-Guest-Photo.jpg';

        $this->assertTrue(isset($archive[$photoPath]));
        $this->assertTrue(isset($archive['manifest.csv']));
        $this->assertFalse(isset($archive['selected-event/videos/'.str_pad((string) $video->id, 6, '0', STR_PAD_LEFT).'-clip.mp4']));
        $this->assertFalse(isset($archive['other-event/images/'.str_pad((string) $otherPhoto->id, 6, '0', STR_PAD_LEFT).'-other.jpg']));

        unset($archive);
        File::delete($archivePath);
    }

    public function test_download_returns_with_an_error_when_filters_match_nothing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();
        $this->createUpload($event, 'photo', 'approved', 'only-photo.jpg');

        $this->actingAs($admin)
            ->from(route('admin.media-downloads.index'))
            ->post(route('admin.media-downloads.download'), [
                'event_id' => $event->id,
                'media_type' => 'video',
                'status' => 'all',
            ])
            ->assertRedirect(route('admin.media-downloads.index'))
            ->assertSessionHas('error', 'No uploaded media matches those filters.');
    }

    private function createUpload(Event $event, string $mediaType, string $status, string $originalFilename): FotoUpload
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $path = 'fotos/event-'.$event->id.'/'.uniqid('upload-', true).'.'.$extension;

        return FotoUpload::query()->create([
            'event_id' => $event->id,
            'file_path' => $path,
            'video_path' => $mediaType === 'video' ? $path : null,
            'media_type' => $mediaType,
            'original_filename' => $originalFilename,
            'file_size' => 1024,
            'mime_type' => $mediaType === 'video' ? 'video/mp4' : 'image/jpeg',
            'status' => $status,
        ]);
    }
}
