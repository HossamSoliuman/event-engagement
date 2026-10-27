<?php

namespace Database\Factories;

use App\Models\ArchivedFotoUpload;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArchivedFotoUpload>
 */
class ArchivedFotoUploadFactory extends Factory
{
    /**
     * Archived rows keep their foto_uploads id; factories pick one far above real ids.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(900_000_000, 999_999_999),
            'event_id' => Event::factory(),
            'file_path' => 'fotos/'.Str::random(20).'.jpg',
            'media_type' => 'photo',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploader_name' => fake()->firstName(),
            'status' => 'approved',
            'approved_at' => now(),
            'archived_at' => now(),
        ];
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'fotos/'.Str::random(20).'.mp4',
            'media_type' => 'video',
            'mime_type' => 'video/mp4',
            'video_duration' => 5.2,
        ]);
    }
}
