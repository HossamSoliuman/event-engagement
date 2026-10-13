<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $candidate = fake()->name();

        return [
            'event_id' => Event::factory(),
            'candidate_name' => $candidate,
            'candidate_slug' => Str::slug($candidate),
            'voter_session' => Str::random(40),
            'voter_ip' => fake()->ipv4(),
        ];
    }

    public function forCandidate(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'candidate_name' => $name,
            'candidate_slug' => Str::slug($name),
        ]);
    }
}
