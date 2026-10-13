<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\VotingScreen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VotingScreen>
 */
class VotingScreenFactory extends Factory
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
            'top_count' => 0,
            'countdown_seconds' => 5,
            'result_seconds' => 0,
        ];
    }

    public function timedResult(int $seconds = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'result_seconds' => $seconds,
        ]);
    }
}
