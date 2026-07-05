<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LandingStyleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_landing_page_defaults_to_classic_style(): void
    {
        $event = Event::factory()->create();

        $response = $this->get("/e/{$event->slug}");

        $response->assertStatus(200);
        $response->assertDontSee('class="landing-clean"', false);
        $response->assertSee('land-header', false);
    }

    public function test_landing_page_renders_clean_style_when_selected(): void
    {
        $event = Event::factory()->cleanStyle()->create([
            'subtitle' => 'Fan Experience',
        ]);

        $response = $this->get("/e/{$event->slug}");

        $response->assertStatus(200);
        $response->assertSee('landing-clean', false);
        $response->assertSee('cl-grid', false);
        $response->assertSee('Fan Experience');
    }

    public function test_clean_landing_shows_custom_hero_text(): void
    {
        $event = Event::factory()->cleanStyle()->create([
            'landing_hero_title' => 'Deine Fan Experience startet hier.',
            'landing_hero_sub' => 'Sei Teil des Stadionentertainments!',
        ]);

        $response = $this->get("/e/{$event->slug}");

        $response->assertStatus(200);
        $response->assertSee('Deine Fan Experience startet hier.');
        $response->assertSee('Sei Teil des Stadionentertainments!');
    }

    public function test_clean_landing_uses_tile_label_and_sublabel(): void
    {
        $event = Event::factory()->cleanStyle()->create([
            'tile_fotobomb_config' => [
                'label' => 'Selfie Wall',
                'sublabel' => 'Presented by UNIQA',
                'bg_color' => '#003b8e',
            ],
        ]);

        $response = $this->get("/e/{$event->slug}");

        $response->assertStatus(200);
        $response->assertSee('Selfie Wall');
        $response->assertSee('Presented by UNIQA');
        $response->assertSee('--cl-card-bg:#003b8e', false);
    }

    public function test_vidiwall_renders_without_frame_by_default(): void
    {
        $event = Event::factory()->create();

        $response = $this->get("/screen/{$event->slug}");

        $response->assertStatus(200);
        $response->assertDontSee('class="fr-bar fr-top"', false);
        $response->assertSee('stage-main', false);
    }

    public function test_vidiwall_renders_sponsor_frame_when_enabled(): void
    {
        $event = Event::factory()->withSponsorFrame()->create();

        $response = $this->get("/screen/{$event->slug}");

        $response->assertStatus(200);
        $response->assertSee('fr-top', false);
        $response->assertSee('Audi Vorsprung durch Technik');
        $response->assertSee('Audi FIS Ski World Cup');
        $response->assertSee('--frame-c: #cc0000', false);
    }

    public function test_vidiwall_frame_falls_back_to_primary_color(): void
    {
        $event = Event::factory()->withSponsorFrame(['frame_color' => ''])->create([
            'primary_color' => '#FF3D00',
        ]);

        $response = $this->get("/screen/{$event->slug}");

        $response->assertStatus(200);
        $response->assertSee('--frame-c: #FF3D00', false);
    }
}
