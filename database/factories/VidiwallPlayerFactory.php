<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\VidiwallPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VidiwallPlayer>
 */
class VidiwallPlayerFactory extends Factory
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
            'state' => VidiwallPlayer::STATE_STOPPED,
            'loop' => false,
            'revision' => 0,
        ];
    }

    public function looping(): static
    {
        return $this->state(fn (array $attributes) => [
            'loop' => true,
        ]);
    }
}
