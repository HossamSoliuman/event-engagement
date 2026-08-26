<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LandingDesignTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * The update endpoint requires every validated field, so build a full payload
     * and only override the parts a test cares about.
     */
    private function payload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'name' => $event->name,
            'is_active' => 1,
            'design' => Event::landingDesignDefaults(),
        ], $overrides);
    }

    public function test_admin_edit_page_renders_the_landing_designer(): void
    {
        $event = Event::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.events.edit', $event));

        $response->assertStatus(200);
        $response->assertSee('ldPanel', false);
        $response->assertSee('ld-phone-screen', false);
        $response->assertSee('design[logo_size]', false);
        $response->assertSee(route('admin.events.preview', $event), false);
    }

    public function test_admin_create_page_renders_the_landing_designer_without_an_event(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.events.create'));

        $response->assertStatus(200);
        $response->assertSee('ldPanel', false);
        $response->assertSee('Create the event first');
    }

    public function test_preview_route_renders_a_draft_event_with_the_live_bridge(): void
    {
        $event = Event::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin())->get(route('admin.events.preview', $event));

        $response->assertStatus(200);
        $response->assertSee('preview-ready', false);
        $response->assertSee('landing-clean', false);
    }

    public function test_guest_landing_page_has_no_preview_bridge(): void
    {
        $event = Event::factory()->create();

        $response = $this->get("/e/{$event->slug}");

        $response->assertStatus(200);
        $response->assertDontSee('preview-ready', false);
    }

    public function test_design_values_are_saved(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'design' => array_merge(Event::landingDesignDefaults(), [
                    'logo_size' => 96,
                    'hashtag_size' => 30,
                    'card_gap' => 6,
                    'logo_position' => 'stacked',
                ]),
            ]))
            ->assertRedirect();

        $design = $event->fresh()->landingDesign();

        $this->assertEqualsWithDelta(96, $design['logo_size'], 0.001);
        $this->assertEqualsWithDelta(30, $design['hashtag_size'], 0.001);
        $this->assertEqualsWithDelta(6, $design['card_gap'], 0.001);
        $this->assertSame('stacked', $design['logo_position']);
    }

    public function test_out_of_range_design_values_are_clamped(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'design' => array_merge(Event::landingDesignDefaults(), [
                    'logo_size' => 9999,
                    'card_gap' => -50,
                    'logo_position' => 'javascript:alert(1)',
                ]),
            ]))
            ->assertRedirect();

        $design = $event->fresh()->landingDesign();

        $this->assertEqualsWithDelta(160, $design['logo_size'], 0.001);
        $this->assertEqualsWithDelta(0, $design['card_gap'], 0.001);
        $this->assertSame('inline', $design['logo_position']);
    }

    public function test_unchecked_toggles_hide_landing_sections(): void
    {
        $event = Event::factory()->create();
        $design = Event::landingDesignDefaults();
        unset($design['hashtag_show'], $design['hero_show'], $design['wordmark_show']);

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, ['design' => $design]))
            ->assertRedirect();

        $saved = $event->fresh()->landingDesign();

        $this->assertFalse($saved['hashtag_show']);
        $this->assertFalse($saved['hero_show']);
        $this->assertFalse($saved['wordmark_show']);
    }

    public function test_per_tile_style_is_saved_and_rendered(): void
    {
        $event = Event::factory()->create(['module_fotobomb' => true]);

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'tile_fotobomb_label' => 'SELFIE CAM',
                'tile_fotobomb_bg_color' => '#003b8e',
                'tile_fotobomb_text_color' => '#ffee00',
                'tile_fotobomb_logo_size' => 55,
                'tile_fotobomb_logo_fit' => 'cover',
                'tile_fotobomb_label_size' => 14,
                'tile_fotobomb_sublabel_size' => 8,
                'tile_fotobomb_media_padding' => 22,
            ]))
            ->assertRedirect();

        $tile = $event->fresh()->tileConfig('fotobomb');

        $this->assertEqualsWithDelta(55, $tile['logo_size'], 0.001);
        $this->assertSame('cover', $tile['logo_fit']);
        $this->assertSame('#ffee00', $tile['text_color']);

        $response = $this->get("/e/{$event->slug}");
        $response->assertStatus(200);
        $response->assertSee('--cl-logo-scale:55%', false);
        $response->assertSee('--cl-fit:cover', false);
        $response->assertSee('--cl-label-size:14px', false);
        $response->assertSee('--cl-media-pad:22px', false);
        $response->assertSee('--cl-card-ink:#ffee00', false);
    }

    public function test_invalid_tile_colours_are_rejected(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.events.update', $event), $this->payload($event, [
                'tile_fotobomb_text_color' => 'red;background:url(x)',
            ]))
            ->assertRedirect();

        $this->assertSame('', $event->fresh()->tileConfig('fotobomb')['text_color']);
    }

    public function test_landing_page_renders_page_level_design_tokens(): void
    {
        $event = Event::factory()->create([
            'landing_design' => array_merge(Event::landingDesignDefaults(), [
                'logo_size' => 72,
                'card_columns' => 3,
                'hashtag_size' => 33,
                'watermark_opacity' => 20,
            ]),
        ]);

        $response = $this->get("/e/{$event->slug}");

        $response->assertStatus(200);
        $response->assertSee('--cl-logo-h: 72px', false);
        $response->assertSee('--cl-cols: 3', false);
        $response->assertSee('--cl-hashtag-size: 33px', false);
        $response->assertSee('--cl-wm-opacity: 0.2', false);
    }

    public function test_hidden_sections_are_marked_hidden_on_the_landing_page(): void
    {
        $event = Event::factory()->create([
            'landing_design' => array_merge(Event::landingDesignDefaults(), [
                'hashtag_show' => false,
                'hero_show' => false,
            ]),
        ]);

        $response = $this->get("/e/{$event->slug}");

        $response->assertStatus(200);
        $response->assertSee('class="cl-hero" hidden', false);
        $response->assertSee('class="cl-hashtag" hidden', false);
    }
}
