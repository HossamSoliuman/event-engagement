<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\LotteryEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LotteryEntry>
 */
class LotteryEntryFactory extends Factory
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
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('+4917########'),
            'email' => fake()->optional()->safeEmail(),
            'entry_token' => Str::random(32),
        ];
    }

    public function winner(int $rank = 1, ?string $prize = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_winner' => true,
            'won_at' => now(),
            'winner_rank' => $rank,
            'prize' => $prize,
        ]);
    }
}
