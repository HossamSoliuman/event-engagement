<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
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
            'question' => rtrim(fake()->sentence(6), '.').'?',
            'type' => SurveyQuestion::TYPE_CHOICE,
            'options' => ['Great', 'Okay', 'Not for me'],
            'is_required' => true,
        ];
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SurveyQuestion::TYPE_TEXT,
            'options' => null,
        ]);
    }

    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }
}
