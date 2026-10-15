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
            ->assertSee('<i data-lucide="qr-code" class="lucide-icon"></i> QR Screen</h3>', false)
            ->assertSee('name="vidiwall_qr_background"', false)
            ->assertSee('name="clear_vidiwall_qr_background"', false)
            ->assertSee($this->event->vidiwall_qr_background_url, false);
    }

    public function test_admin_saves_the_qr_screen_call_to_action(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload([
                'vidiwall_qr_headline' => 'Scan the code and win a ski pass',
                'vidiwall_qr_small_text' => 'Winners are drawn at 18:00',
                'vidiwall_qr_text_color' => '#1A2B3C',
                'vidiwall_qr_panel' => 'glass',
                'vidiwall_qr_panel_color' => '#fff',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.events.show', $this->event));

        $this->assertSame([
            'headline' => 'Scan the code and win a ski pass',
            'small_text' => 'Winners are drawn at 18:00',
            'text_color' => '#1A2B3C',
            'panel' => 'glass',
            'panel_color' => '#fff',
        ], $this->event->fresh()->qrScreenCallToAction());
    }

    public function test_the_qr_screen_texts_can_be_cleared_and_are_kept_when_left_out(): void
    {
        $this->event->update(['vidiwall_qr_headline' => 'Scan and win', 'vidiwall_qr_small_text' => 'Prizes daily', 'vidiwall_qr_panel' => 'solid']);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload(['name' => 'Sölden Opening']))
            ->assertSessionHasNoErrors();

        $this->event->refresh();
        $this->assertSame('Scan and win', $this->event->vidiwall_qr_headline);
        $this->assertSame('solid', $this->event->vidiwall_qr_panel);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload([
                'vidiwall_qr_headline' => '',
                'vidiwall_qr_small_text' => '   ',
                'vidiwall_qr_text_color' => '',
                'vidiwall_qr_panel' => 'none',
            ]))
            ->assertSessionHasNoErrors();

        $this->event->refresh();
        $this->assertNull($this->event->vidiwall_qr_headline);
        $this->assertNull($this->event->vidiwall_qr_small_text);
        $this->assertNull($this->event->vidiwall_qr_text_color);
        $this->assertSame('none', $this->event->vidiwall_qr_panel);
        $this->assertSame('#ffffff', $this->event->qrScreenCallToAction()['text_color']);
        $this->assertFalse($this->event->hasQrScreenCallToAction());
    }

    public function test_the_qr_screen_call_to_action_is_validated(): void
    {
        $invalid = [
            'vidiwall_qr_headline' => [str_repeat('a', 81), ['not', 'text']],
            'vidiwall_qr_small_text' => [str_repeat('a', 141)],
            'vidiwall_qr_text_color' => ['white', '#12345', '#ffffff; background: url(x)', 'ffffff'],
            'vidiwall_qr_panel' => ['blur', '', 'Solid'],
            'vidiwall_qr_panel_color' => ['#ggg', '#1234567', 'rgba(0,0,0,.5)'],
        ];

        foreach ($invalid as $field => $values) {
            foreach ($values as $value) {
                $this->actingAs($this->admin)
                    ->put(route('admin.events.update', $this->event), $this->payload([$field => $value]))
                    ->assertSessionHasErrors($field);
            }
        }

        $this->assertFalse($this->event->fresh()->hasQrScreenCallToAction());
        $this->assertNull($this->event->fresh()->vidiwall_qr_text_color);
    }

    public function test_the_qr_screen_validation_messages_name_the_fields(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload([
                'vidiwall_qr_headline' => str_repeat('a', 81),
                'vidiwall_qr_text_color' => 'white',
                'vidiwall_qr_panel' => 'blur',
                'vidiwall_qr_panel_color' => 'black',
            ]))
            ->assertSessionHasErrors([
                'vidiwall_qr_headline' => 'The QR screen headline must not be greater than 80 characters.',
                'vidiwall_qr_text_color' => 'The QR screen text colour must be a hex colour like #FFFFFF.',
                'vidiwall_qr_panel' => 'Choose none, solid or glass for the QR screen panel.',
                'vidiwall_qr_panel_color' => 'The QR screen panel colour must be a hex colour like #000000.',
            ]);
    }

    public function test_a_new_event_saves_its_qr_screen_call_to_action(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.events.store'), [
                'name' => 'Sölden Opening',
                'design' => Event::landingDesignDefaults(),
                'vidiwall_qr_headline' => 'Jetzt scannen und gewinnen!',
                'vidiwall_qr_panel' => 'solid',
                'vidiwall_qr_panel_color' => '#E30613',
            ])
            ->assertSessionHasNoErrors();

        $event = Event::query()->where('name', 'Sölden Opening')->latest('id')->firstOrFail();
        $this->assertSame('Jetzt scannen und gewinnen!', $event->vidiwall_qr_headline);
        $this->assertSame('solid', $event->vidiwall_qr_panel);
        $this->assertSame('#E30613', $event->vidiwall_qr_panel_color);
    }

    public function test_the_wall_shows_the_headline_qr_code_and_small_text_in_a_panel(): void
    {
        $this->event->update([
            'qr_code_path' => 'qrcodes/event-'.$this->event->id.'.svg',
            'vidiwall_qr_headline' => 'Scan the code and win…',
            'vidiwall_qr_small_text' => 'Daily prizes for fans',
            'vidiwall_qr_text_color' => '#111111',
            'vidiwall_qr_panel' => 'glass',
            'vidiwall_qr_panel_color' => '#0a1a3a',
        ]);

        $response = $this->get(route('vidiwall.show', $this->event->slug))
            ->assertOk()
            ->assertSee('<div class="qr-screen has-cta" id="qrScreen" hidden', false)
            ->assertSee('<div class="qr-cta is-glass"', false)
            ->assertSee('style="--qr-ink: #111111; --qr-panel: #0a1a3a"', false)
            ->assertSeeInOrder([
                '<div class="qr-cta-headline">Scan the code and win…</div>',
                '<div class="qr-cta-code">',
                $this->event->qr_code_url,
                '<div class="qr-cta-text">Daily prizes for fans</div>',
            ], false);

        $this->assertSame(1, substr_count($response->getContent(), 'class="qr-cta-headline"'));
    }

    public function test_the_wall_shows_the_qr_screen_texts_exactly_as_typed(): void
    {
        $this->event->update([
            'vidiwall_qr_headline' => "Scan & <b>win</b>\nJetzt gewinnen!",
            'vidiwall_qr_small_text' => '<script>alert(1)</script>',
            'landing_design' => array_merge(Event::landingDesignDefaults(), ['guest_language' => 'de']),
        ]);

        $content = $this->get(route('vidiwall.show', $this->event->slug))->assertOk()->getContent();

        $this->assertStringContainsString("<div class=\"qr-cta-headline\">Scan &amp; &lt;b&gt;win&lt;/b&gt;\nJetzt gewinnen!</div>", $content);
        $this->assertStringContainsString('<div class="qr-cta-text">&lt;script&gt;alert(1)&lt;/script&gt;</div>', $content);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $content);
        $this->assertStringContainsString('white-space: pre-line', $content);
    }

    public function test_the_wall_uses_white_text_and_a_black_panel_by_default(): void
    {
        $this->event->update(['vidiwall_qr_headline' => 'Scan and win']);

        $this->get(route('vidiwall.show', $this->event->slug))
            ->assertOk()
            ->assertSee('<div class="qr-cta is-none"', false)
            ->assertSee('style="--qr-ink: #ffffff; --qr-panel: #000000"', false)
            ->assertDontSee('class="qr-cta-text"', false);
    }

    public function test_the_wall_never_echoes_a_stored_colour_that_is_not_hex(): void
    {
        Event::query()->whereKey($this->event->id)->update([
            'vidiwall_qr_headline' => 'Scan and win',
            'vidiwall_qr_text_color' => 'red;}body{display:none',
            'vidiwall_qr_panel' => 'neon',
            'vidiwall_qr_panel_color' => 'url(x)',
        ]);

        $this->get(route('vidiwall.show', $this->event->slug))
            ->assertOk()
            ->assertSee('<div class="qr-cta is-none"', false)
            ->assertSee('style="--qr-ink: #ffffff; --qr-panel: #000000"', false)
            ->assertDontSee('red;}body{display:none', false)
            ->assertDontSee('url(x)', false);
    }

    public function test_a_panel_without_texts_still_frames_the_qr_code(): void
    {
        $this->event->update(['qr_code_path' => 'qrcodes/event-'.$this->event->id.'.svg', 'vidiwall_qr_panel' => 'solid', 'vidiwall_qr_panel_color' => '#E30613']);

        $content = $this->get(route('vidiwall.show', $this->event->slug))->assertOk()->getContent();
        $screen = str($content)->after('id="qrScreen"')->toString();

        $this->assertStringContainsString('<div class="qr-screen has-cta" id="qrScreen" hidden', $content);
        $this->assertStringContainsString('<div class="qr-cta is-solid"', $screen);
        $this->assertStringContainsString('--qr-panel: #E30613', $screen);
        $this->assertStringContainsString('<div class="qr-cta-code">', $screen);
        $this->assertStringNotContainsString('class="qr-cta-headline"', $screen);
        $this->assertStringNotContainsString('class="qr-cta-text"', $screen);
    }

    public function test_the_call_to_action_shows_the_guest_url_without_a_qr_code(): void
    {
        $this->event->update(['vidiwall_qr_headline' => 'Scan and win', 'vidiwall_qr_panel' => 'solid']);

        $screen = str($this->get(route('vidiwall.show', $this->event->slug))->getContent())->after('id="qrScreen"')->before('</script>')->toString();

        $this->assertStringContainsString('<div class="qr-fallback">'.$this->event->getGuestUrl().'</div>', $screen);
        $this->assertStringNotContainsString('qr-cta-code', $screen);
        $this->assertStringNotContainsString('qr-card', $screen);
    }

    public function test_the_wall_qr_screen_is_unchanged_without_texts_or_a_panel(): void
    {
        $this->event->update([
            'qr_code_path' => 'qrcodes/event-'.$this->event->id.'.svg',
            'vidiwall_qr_text_color' => '#E30613',
            'vidiwall_qr_panel' => 'none',
            'vidiwall_qr_panel_color' => '#000000',
        ]);

        $content = $this->get(route('vidiwall.show', $this->event->slug))->assertOk()->getContent();
        $screen = str($content)->after('id="qrScreen" hidden')->before('<script>')->toString();

        $this->assertStringContainsString('<div class="qr-screen" id="qrScreen" hidden', $content);
        $this->assertStringNotContainsString('qr-cta', $screen);
        $this->assertStringNotContainsString('--qr-ink', $screen);
        $this->assertStringContainsString('<div class="qr-card">', $screen);
        $this->assertStringContainsString($this->event->qr_code_url, $screen);
    }

    public function test_the_edit_form_shows_the_qr_screen_call_to_action(): void
    {
        $this->event->update([
            'vidiwall_qr_headline' => 'Scan & win',
            'vidiwall_qr_small_text' => 'Daily prizes',
            'vidiwall_qr_text_color' => '#111111',
            'vidiwall_qr_panel' => 'glass',
            'vidiwall_qr_panel_color' => '#0a1a3a',
        ]);

        $content = $this->actingAs($this->admin)
            ->get(route('admin.events.edit', $this->event))
            ->assertOk()
            ->assertSeeInOrder([
                'for="vidiwall_qr_headline">Headline</label>',
                'for="vidiwall_qr_small_text">Small text</label>',
                'id="qrPanelLabel">Panel</span>',
                'for="vidiwall_qr_text_color">Text colour</label>',
                'for="vidiwall_qr_panel_color">Panel colour</label>',
                'name="vidiwall_qr_background"',
            ], false)
            ->getContent();

        $this->assertStringContainsString('value="Scan &amp; win"', $this->tagContaining($content, 'name="vidiwall_qr_headline"'));
        $this->assertStringContainsString('value="Daily prizes"', $this->tagContaining($content, 'name="vidiwall_qr_small_text"'));
        $this->assertStringContainsString('value="#111111"', $this->tagContaining($content, 'name="vidiwall_qr_text_color"'));
        $this->assertStringContainsString('value="#0a1a3a"', $this->tagContaining($content, 'name="vidiwall_qr_panel_color"'));
        $this->assertStringContainsString(' checked', $this->tagContaining($content, 'name="vidiwall_qr_panel" value="glass"'));
        $this->assertStringNotContainsString(' checked', $this->tagContaining($content, 'name="vidiwall_qr_panel" value="none"'));
        $this->assertStringNotContainsString('disabled', $this->tagContaining($content, 'id="qrPanelColor"'));
    }

    public function test_the_create_form_defaults_to_white_text_and_no_panel(): void
    {
        $content = $this->actingAs($this->admin)->get(route('admin.events.create'))->assertOk()->getContent();

        $this->assertStringContainsString('maxlength="80" value=""', $this->tagContaining($content, 'name="vidiwall_qr_headline"'));
        $this->assertStringContainsString('maxlength="140"', $this->tagContaining($content, 'name="vidiwall_qr_small_text"'));
        $this->assertStringContainsString('value="#ffffff"', $this->tagContaining($content, 'name="vidiwall_qr_text_color"'));
        $this->assertStringContainsString('value="#000000"', $this->tagContaining($content, 'name="vidiwall_qr_panel_color"'));
        $this->assertStringContainsString(' checked', $this->tagContaining($content, 'name="vidiwall_qr_panel" value="none"'));
        $this->assertStringContainsString('disabled', $this->tagContaining($content, 'id="qrPanelColor"'));
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

    /**
     * The opening tag around the first occurrence of the snippet, with its whitespace collapsed.
     */
    private function tagContaining(string $content, string $snippet): string
    {
        $this->assertStringContainsString($snippet, $content);

        $tag = '<'.str($content)->before($snippet)->afterLast('<').$snippet.str($content)->after($snippet)->before('>').'>';

        return preg_replace('/\s+/', ' ', $tag);
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
