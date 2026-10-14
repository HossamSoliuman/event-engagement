<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FotoUpload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FotoBombUploadTest extends TestCase
{
    use DatabaseTransactions;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        $this->event = Event::factory()->create(['module_fotobomb' => true]);
    }

    public function test_a_six_second_video_is_accepted(): void
    {
        $this->uploadVideo(6.0)->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('foto_uploads', [
            'event_id' => $this->event->id,
            'media_type' => 'video',
            'video_duration' => 6.0,
            'status' => 'pending',
        ]);
    }

    public function test_a_video_slightly_over_six_seconds_is_accepted(): void
    {
        $this->uploadVideo(6.3)->assertOk()->assertJsonPath('success', true);

        $this->assertSame(1, FotoUpload::where('event_id', $this->event->id)->count());
    }

    public function test_a_seven_second_video_is_refused(): void
    {
        $this->post(route('fotobomb.upload', $this->event->slug), [
            'video' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
            'video_duration' => 7,
        ])->assertSessionHasErrors('video_duration');

        $this->assertSame(0, FotoUpload::where('event_id', $this->event->id)->count());
    }

    public function test_a_video_without_a_duration_is_refused(): void
    {
        $this->post(route('fotobomb.upload', $this->event->slug), [
            'video' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
        ])->assertSessionHasErrors('video_duration');
    }

    public function test_a_photo_upload_needs_no_duration(): void
    {
        $this->postJson(route('fotobomb.upload', $this->event->slug), [
            'photo' => UploadedFile::fake()->image('snap.jpg', 800, 600),
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('foto_uploads', [
            'event_id' => $this->event->id,
            'media_type' => 'photo',
            'video_duration' => null,
        ]);
    }

    public function test_the_guest_page_shows_the_six_second_limit(): void
    {
        $this->get(route('event.landing', $this->event->slug))
            ->assertOk()
            ->assertSee('max 6 sec')
            ->assertSee('MAX_VIDEO_SECONDS = 6', false)
            ->assertSee('MAX_VIDEO_ACCEPTED = 6.5', false);
    }

    private function uploadVideo(float $seconds): TestResponse
    {
        return $this->postJson(route('fotobomb.upload', $this->event->slug), [
            'video' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
            'video_duration' => $seconds,
        ]);
    }
}
