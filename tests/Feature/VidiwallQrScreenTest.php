<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VidiwallQrScreenTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->event = Event::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_qr_pauses_a_running_playlist_and_reaches_the_wall(): void
    {
        $first = $this->upload();
        $this->upload();

        Carbon::setTestNow('2026-10-15 20:00:00');
        $this->event->player()->play();

        Carbon::setTestNow('2026-10-15 20:00:02.500');
        $this->qr(true)
            ->assertOk()
            ->assertJsonPath('player.qr_screen', true)
            ->assertJsonPath('player.state', 'paused')
            ->assertJsonPath('player.elapsed_ms', 2500)
            ->assertJsonPath('now_playing.id', $first->id);

        $this->feed()
            ->assertJsonPath('qr_screen', true)
            ->assertJsonPath('state', 'paused')
            ->assertJsonPath('foto.id', $first->id)
            ->assertJsonPath('elapsed_ms', 2500);
    }

    public function test_pressing_qr_again_resumes_the_playlist_where_it_left_off(): void
    {
        $first = $this->upload();
        $second = $this->upload();

        Carbon::setTestNow('2026-10-15 20:00:00');
        $this->event->player()->play();

        Carbon::setTestNow('2026-10-15 20:00:02.500');
        $this->qr(true);

        Carbon::setTestNow('2026-10-15 20:10:00');
        $this->qr(false)
            ->assertJsonPath('player.qr_screen', false)
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('player.elapsed_ms', 2500)
            ->assertJsonPath('now_playing.id', $first->id);

        $this->feed()->assertJsonPath('qr_screen', false)->assertJsonPath('foto.id', $first->id);

        Carbon::setTestNow('2026-10-15 20:10:01.500');
        $this->feed()->assertJsonPath('foto.id', $second->id);
    }

    public function test_play_takes_the_qr_screen_down_and_resumes(): void
    {
        $first = $this->upload();
        $this->upload();

        Carbon::setTestNow('2026-10-15 20:00:00');
        $this->event->player()->play();

        Carbon::setTestNow('2026-10-15 20:00:01');
        $this->qr(true);

        Carbon::setTestNow('2026-10-15 20:05:00');
        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('player.qr_screen', false)
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('player.elapsed_ms', 1000)
            ->assertJsonPath('now_playing.id', $first->id);

        $this->feed()->assertJsonPath('qr_screen', false)->assertJsonPath('state', 'playing');
    }

    public function test_the_playlist_does_not_move_on_under_the_qr_screen(): void
    {
        $first = $this->upload();
        $this->upload();

        Carbon::setTestNow('2026-10-15 20:00:00');
        $this->event->player()->play();
        $revision = $this->feed()->json('revision');
        $this->qr(true);

        Carbon::setTestNow('2026-10-15 20:30:00');
        $this->getJson(route('vidiwall.feed', ['slug' => $this->event->slug, 'done' => $first->id, 'rev' => $revision]))
            ->assertJsonPath('qr_screen', true)
            ->assertJsonPath('state', 'paused')
            ->assertJsonPath('foto.id', $first->id);
    }

    public function test_qr_over_a_paused_playlist_leaves_it_paused_when_it_comes_down(): void
    {
        $first = $this->upload();
        $this->event->player()->play();
        $this->event->player()->pause();

        $this->qr(true)->assertJsonPath('player.qr_screen', true)->assertJsonPath('player.state', 'paused');

        $this->qr(false)
            ->assertJsonPath('player.qr_screen', false)
            ->assertJsonPath('player.state', 'paused')
            ->assertJsonPath('now_playing.id', $first->id);
    }

    public function test_qr_while_stopped_comes_down_to_the_idle_screen(): void
    {
        $this->upload();

        $this->qr(true)
            ->assertJsonPath('player.qr_screen', true)
            ->assertJsonPath('player.state', 'stopped')
            ->assertJsonPath('now_playing', null);

        $this->feed()->assertJsonPath('qr_screen', true)->assertJsonPath('foto', null);

        $this->qr(false)
            ->assertJsonPath('player.qr_screen', false)
            ->assertJsonPath('player.state', 'stopped');

        $this->feed()->assertJsonPath('qr_screen', false)->assertJsonPath('foto', null);
    }

    public function test_stop_takes_the_qr_screen_down_and_shows_the_idle_screen(): void
    {
        $first = $this->upload();
        $this->upload();
        $this->event->player()->play();
        $this->qr(true);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.stop', $this->event))
            ->assertJsonPath('player.qr_screen', false)
            ->assertJsonPath('player.state', 'stopped')
            ->assertJsonPath('now_playing', null);

        $this->feed()->assertJsonPath('qr_screen', false)->assertJsonPath('foto', null);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertJsonPath('now_playing.id', $first->id);
    }

    public function test_stepping_under_the_qr_screen_keeps_it_up_and_moves_where_the_playlist_resumes(): void
    {
        $this->upload();
        $second = $this->upload();
        Carbon::setTestNow('2026-10-15 20:00:00');
        $this->event->player()->play();
        $this->qr(true);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.next', $this->event))
            ->assertJsonPath('player.qr_screen', true)
            ->assertJsonPath('player.state', 'paused')
            ->assertJsonPath('now_playing.id', $second->id);

        $this->qr(false)
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.id', $second->id)
            ->assertJsonPath('player.elapsed_ms', 0);
    }

    public function test_playing_an_item_from_the_list_takes_the_qr_screen_down(): void
    {
        $this->upload();
        $second = $this->upload();
        $this->event->player()->play();
        $this->qr(true);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play-from', [$this->event, $second]))
            ->assertJsonPath('player.qr_screen', false)
            ->assertJsonPath('player.state', 'playing')
            ->assertJsonPath('now_playing.id', $second->id);
    }

    public function test_rejecting_the_held_item_keeps_the_qr_screen_up(): void
    {
        $first = $this->upload();
        $second = $this->upload();
        $this->event->player()->play();
        $this->qr(true);

        $this->actingAs($this->admin)->postJson(route('admin.fotos.reject', $first))->assertOk();

        $this->feed()
            ->assertJsonPath('qr_screen', true)
            ->assertJsonPath('state', 'paused')
            ->assertJsonPath('foto.id', $second->id);

        $this->qr(false)->assertJsonPath('player.state', 'playing')->assertJsonPath('now_playing.id', $second->id);
    }

    public function test_qr_comes_down_stopped_when_the_held_playlist_ran_out(): void
    {
        $only = $this->upload();
        $this->event->player()->play();
        $this->qr(true);

        $this->actingAs($this->admin)->postJson(route('admin.fotos.reject', $only))->assertOk();

        $this->qr(false)
            ->assertJsonPath('player.qr_screen', false)
            ->assertJsonPath('player.state', 'stopped')
            ->assertJsonPath('now_playing', null);
    }

    public function test_play_with_nothing_to_play_leaves_the_qr_screen_up(): void
    {
        $this->qr(true);

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.play', $this->event))
            ->assertStatus(422)
            ->assertJsonPath('player.qr_screen', true);
    }

    public function test_each_qr_press_bumps_the_revision_once(): void
    {
        $this->upload();
        $this->event->player()->play();
        $start = $this->event->player()->fresh()->revision;

        $on = $this->qr(true)->json('player.revision');
        $again = $this->qr(true)->json('player.revision');
        $off = $this->qr(false)->json('player.revision');

        $this->assertGreaterThan($start, $on);
        $this->assertSame($on, $again);
        $this->assertGreaterThan($on, $off);
    }

    public function test_qr_requires_a_boolean(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.player.qr', $this->event), ['qr' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Choose whether the QR screen should be shown.');

        $this->actingAs($this->admin)
            ->postJson(route('admin.player.qr', $this->event))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Choose whether the QR screen should be shown.');

        $this->assertFalse((bool) $this->event->vidiwallPlayer()->first()?->qr_screen);
    }

    public function test_qr_requires_a_login(): void
    {
        $this->postJson(route('admin.player.qr', $this->event), ['qr' => true])->assertUnauthorized();

        $this->assertNull($this->event->vidiwallPlayer()->first());
    }

    public function test_assigned_moderators_can_show_the_qr_screen_but_others_cannot(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);
        $otherEvent = Event::factory()->create();

        $this->actingAs($moderator)
            ->postJson(route('moderator.player.qr', $this->event), ['qr' => true])
            ->assertOk()
            ->assertJsonPath('player.qr_screen', true);

        $this->actingAs($moderator)
            ->postJson(route('moderator.player.qr', $otherEvent), ['qr' => true])
            ->assertForbidden();
    }

    public function test_the_player_page_shows_the_qr_toggle_and_preview(): void
    {
        $this->event->update(['qr_code_path' => 'qrcodes/event-'.$this->event->id.'.svg', 'vidiwall_qr_background_path' => 'vidiwall-backgrounds/qr.jpg']);

        $this->actingAs($this->admin)
            ->get(route('admin.player.index', $this->event))
            ->assertOk()
            ->assertSee('id="vpQr" aria-pressed="false"', false)
            ->assertSee('id="vpQrScreen"', false)
            ->assertSee($this->event->qr_code_url, false)
            ->assertSee("background-image: url('{$this->event->vidiwall_qr_background_url}')", false)
            ->assertSee(json_encode(route('admin.player.qr', $this->event)), false);
    }

    public function test_the_wall_renders_the_qr_screen_on_its_own_background(): void
    {
        $this->event->update(['qr_code_path' => 'qrcodes/event-'.$this->event->id.'.svg', 'vidiwall_qr_background_path' => 'vidiwall-backgrounds/qr.jpg']);
        $url = $this->event->vidiwall_qr_background_url;

        $this->get(route('vidiwall.show', $this->event->slug))
            ->assertOk()
            ->assertSee('<div class="qr-screen" id="qrScreen" hidden', false)
            ->assertSee("background-image: url('{$url}')", false)
            ->assertSee('<link rel="preload" as="image" href="'.$url.'">', false)
            ->assertSee($this->event->qr_code_url, false);
    }

    public function test_the_wall_qr_screen_falls_back_to_the_background_colour_and_guest_url(): void
    {
        $response = $this->get(route('vidiwall.show', $this->event->slug))->assertOk();

        $screen = str($response->getContent())->after('id="qrScreen"')->before('</div>')->toString();

        $this->assertStringNotContainsString('background-image', $screen);
        $this->assertStringContainsString($this->event->getGuestUrl(), $screen);
        $this->assertStringNotContainsString('qr-card', $screen);
    }

    public function test_admin_uploads_and_clears_a_qr_screen_background(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload([
                'vidiwall_qr_background' => UploadedFile::fake()->image('qr-art.jpg', 1920, 1080),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.events.show', $this->event));

        $path = $this->event->fresh()->vidiwall_qr_background_path;
        $this->assertStringStartsWith('vidiwall-backgrounds/', $path);
        Storage::disk('media')->assertExists($path);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload(['name' => 'Sölden Opening']))
            ->assertSessionHasNoErrors();

        $this->assertSame($path, $this->event->fresh()->vidiwall_qr_background_path);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload(['clear_vidiwall_qr_background' => 1]))
            ->assertSessionHasNoErrors();

        $this->assertNull($this->event->fresh()->vidiwall_qr_background_path);
    }

    public function test_a_qr_screen_background_must_be_a_jpg_png_or_webp(): void
    {
        foreach ([UploadedFile::fake()->create('art.pdf', 200, 'application/pdf'), UploadedFile::fake()->image('loop.gif', 1920, 1080)] as $file) {
            $this->actingAs($this->admin)
                ->put(route('admin.events.update', $this->event), $this->payload(['vidiwall_qr_background' => $file]))
                ->assertSessionHasErrors('vidiwall_qr_background');
        }

        $this->assertNull($this->event->fresh()->vidiwall_qr_background_path);
    }

    public function test_wall_and_qr_screen_backgrounds_are_independent(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload([
                'vidiwall_background' => UploadedFile::fake()->image('sponsor.jpg', 1920, 1080),
                'vidiwall_qr_background' => UploadedFile::fake()->image('qr-art.jpg', 1920, 1080),
            ]))
            ->assertSessionHasNoErrors();

        $this->event->refresh();
        $wall = $this->event->vidiwall_background_path;
        $this->assertNotSame($wall, $this->event->vidiwall_qr_background_path);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload(['clear_vidiwall_qr_background' => 1]))
            ->assertSessionHasNoErrors();

        $this->event->refresh();
        $this->assertNull($this->event->vidiwall_qr_background_path);
        $this->assertSame($wall, $this->event->vidiwall_background_path);
    }

    public function test_the_edit_form_shows_the_current_qr_screen_background(): void
    {
        $this->event->update(['vidiwall_qr_background_path' => 'vidiwall-backgrounds/qr.jpg']);

        $this->actingAs($this->admin)
            ->get(route('admin.events.edit', $this->event))
            ->assertOk()
            ->assertSee('QR Screen Background')
            ->assertSee('name="vidiwall_qr_background"', false)
            ->assertSee('name="clear_vidiwall_qr_background"', false)
            ->assertSee($this->event->vidiwall_qr_background_url, false);
    }

    public function test_deleting_the_event_removes_its_qr_screen_background(): void
    {
        $path = 'vidiwall-backgrounds/'.fake()->uuid().'.jpg';
        Storage::disk('media')->put($path, 'x');
        $this->event->update(['vidiwall_qr_background_path' => $path]);

        $this->actingAs($this->admin)
            ->delete(route('admin.events.destroy', $this->event))
            ->assertRedirect(route('admin.events.index'));

        Storage::disk('media')->assertMissing($path);
    }

    private function qr(bool $show): TestResponse
    {
        return $this->actingAs($this->admin)->postJson(route('admin.player.qr', $this->event), ['qr' => $show]);
    }

    private function feed(): TestResponse
    {
        return $this->getJson(route('vidiwall.feed', $this->event->slug));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->event->name,
            'is_active' => 1,
            'design' => Event::landingDesignDefaults(),
        ], $overrides);
    }

    /**
     * An approved photo at the end of the playlist, as approving it would put it.
     */
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
