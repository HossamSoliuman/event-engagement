<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VidiwallBackgroundTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'name' => $event->name,
            'is_active' => 1,
            'design' => Event::landingDesignDefaults(),
        ], $overrides);
    }

    private function eventWithBackground(string $path = 'vidiwall-backgrounds/sponsor.jpg'): Event
    {
        Storage::disk('media')->put($path, 'x');

        return Event::factory()->create(['is_active' => true, 'vidiwall_background_path' => $path]);
    }

    public function test_admin_uploads_a_wall_background(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'vidiwall_background' => UploadedFile::fake()->image('uniqa.jpg', 1920, 1080),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.events.edit', $event).'#details');

        $path = $event->fresh()->vidiwall_background_path;

        $this->assertNotNull($path);
        $this->assertStringStartsWith('vidiwall-backgrounds/', $path);
        Storage::disk('media')->assertExists($path);
    }

    public function test_webp_and_png_backgrounds_are_accepted(): void
    {
        $event = Event::factory()->create();

        foreach (['art.png', 'art.webp'] as $name) {
            $this->actingAs($this->admin())
                ->put(route('admin.events.update', $event), $this->payload($event, [
                    'vidiwall_background' => UploadedFile::fake()->image($name, 1920, 1080),
                ]))
                ->assertSessionHasNoErrors();

            $this->assertStringEndsWith(pathinfo($name, PATHINFO_EXTENSION), $event->fresh()->vidiwall_background_path);
        }
    }

    public function test_a_non_image_background_is_refused(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'vidiwall_background' => UploadedFile::fake()->create('artwork.pdf', 200, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('vidiwall_background');

        $this->assertNull($event->fresh()->vidiwall_background_path);
    }

    public function test_a_gif_background_is_refused(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'vidiwall_background' => UploadedFile::fake()->image('loop.gif', 1920, 1080),
            ]))
            ->assertSessionHasErrors('vidiwall_background');
    }

    public function test_a_background_over_ten_megabytes_is_refused(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'vidiwall_background' => UploadedFile::fake()->image('huge.jpg', 1920, 1080)->size(10241),
            ]))
            ->assertSessionHasErrors('vidiwall_background');

        $this->assertNull($event->fresh()->vidiwall_background_path);
    }

    public function test_clearing_removes_the_background(): void
    {
        $event = $this->eventWithBackground();

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, ['clear_vidiwall_background' => 1]))
            ->assertSessionHasNoErrors();

        $this->assertNull($event->fresh()->vidiwall_background_path);
    }

    public function test_updating_other_fields_keeps_the_background(): void
    {
        $event = $this->eventWithBackground();

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, ['name' => 'Sölden Opening']))
            ->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertSame('Sölden Opening', $event->name);
        $this->assertSame('vidiwall-backgrounds/sponsor.jpg', $event->vidiwall_background_path);
    }

    public function test_the_wall_renders_the_background_and_hides_the_frame_when_set(): void
    {
        $event = $this->eventWithBackground();
        $url = $event->vidiwall_background_url;

        $this->get(route('vidiwall.show', $event->slug))
            ->assertOk()
            ->assertSee('class="screen has-bg"', false)
            ->assertSee("--wall-bg: url('{$url}')", false)
            ->assertSee('<link rel="preload" as="image" href="'.$url.'">', false)
            ->assertSee('const HAS_WALL_BG = true;', false);
    }

    public function test_the_wall_keeps_the_frame_when_no_background_is_set(): void
    {
        $event = Event::factory()->create(['is_active' => true]);

        $this->get(route('vidiwall.show', $event->slug))
            ->assertOk()
            ->assertSee('class="screen"', false)
            ->assertDontSee('has-bg"', false)
            ->assertDontSee('--wall-bg:', false)
            ->assertDontSee('vidiwall-backgrounds/', false)
            ->assertSee('const HAS_WALL_BG = false;', false);
    }

    public function test_the_wall_shows_no_slide_progress_bar_with_or_without_a_background(): void
    {
        $plain = Event::factory()->create(['is_active' => true]);

        foreach ([$plain, $this->eventWithBackground()] as $event) {
            $this->get(route('vidiwall.show', $event->slug))
                ->assertOk()
                ->assertDontSee('slideProgress', false)
                ->assertDontSee('slide-progress', false);
        }
    }

    public function test_the_edit_form_shows_the_current_background(): void
    {
        $event = $this->eventWithBackground();

        $this->actingAs($this->admin())
            ->get(route('admin.events.edit', $event))
            ->assertOk()
            ->assertSee('name="vidiwall_background"', false)
            ->assertSee('name="clear_vidiwall_background"', false)
            ->assertSee($event->vidiwall_background_url, false);
    }

    public function test_deleting_the_event_removes_its_background(): void
    {
        $event = $this->eventWithBackground('vidiwall-backgrounds/'.fake()->uuid().'.jpg');
        $path = $event->vidiwall_background_path;

        $this->actingAs($this->admin())
            ->delete(route('admin.events.destroy', $event))
            ->assertRedirect(route('admin.events.index'));

        Storage::disk('media')->assertMissing($path);
    }

    public function test_deleting_a_duplicate_keeps_the_shared_background(): void
    {
        $original = $this->eventWithBackground('vidiwall-backgrounds/'.fake()->uuid().'.jpg');
        $path = $original->vidiwall_background_path;
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.events.duplicate', $original));

        $copy = Event::where('name', $original->name.' (Copy)')->latest('id')->firstOrFail();
        $this->assertSame($path, $copy->vidiwall_background_path);

        $this->actingAs($admin)
            ->delete(route('admin.events.destroy', $copy))
            ->assertRedirect(route('admin.events.index'));

        Storage::disk('media')->assertExists($path);
        $this->assertSame($path, $original->fresh()->vidiwall_background_path);
    }

    public function test_landing_and_wall_backgrounds_are_independent(): void
    {
        $event = Event::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'background_image' => UploadedFile::fake()->image('crowd.jpg', 1170, 2532),
                'vidiwall_background' => UploadedFile::fake()->image('uniqa.jpg', 1920, 1080),
            ]))
            ->assertSessionHasNoErrors();

        $event->refresh();
        $landing = $event->background_image_path;
        $wall = $event->vidiwall_background_path;
        $this->assertNotSame($landing, $wall);

        $this->actingAs($admin)
            ->put(route('admin.events.update', $event), $this->payload($event, ['clear_vidiwall_background' => 1]))
            ->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertNull($event->vidiwall_background_path);
        $this->assertSame($landing, $event->background_image_path);

        $this->actingAs($admin)
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'vidiwall_background' => UploadedFile::fake()->image('uniqa.jpg', 1920, 1080),
                'clear_background_image' => 1,
            ]))
            ->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertNull($event->background_image_path);
        $this->assertNotNull($event->vidiwall_background_path);
    }
}
