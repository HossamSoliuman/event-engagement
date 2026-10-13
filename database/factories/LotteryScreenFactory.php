<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\LotteryScreen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LotteryScreen>
 */
class LotteryScreenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'draw_on_screen' => false,
            'countdown_seconds' => 5,
            'drawing_seconds' => 6,
            'result_seconds' => 30,
            'name_format' => LotteryScreen::NAME_FULL,
        ];
    }

    public function onScreen(): static
    {
        return $this->state(fn (array $attributes) => [
            'draw_on_screen' => true,
        ]);
    }
}
