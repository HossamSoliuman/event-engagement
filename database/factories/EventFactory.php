<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'is_active' => true,
        ];
    }

    public function cleanStyle(): static
    {
        return $this->state(fn (array $attributes) => [
            'landing_style' => 'clean',
        ]);
    }

    public function withSponsorFrame(array $overrides = []): static
    {
        return $this->state(fn (array $attributes) => [
            'vidiwall_frame_config' => array_merge([
                'enabled' => true,
                'frame_color' => '#cc0000',
                'text_color' => '#ffffff',
                'top_text' => 'Audi Vorsprung durch Technik',
                'bottom_text' => '',
                'side_text' => 'Audi FIS Ski World Cup',
                'logo_path' => null,
            ], $overrides),
        ]);
    }
}
