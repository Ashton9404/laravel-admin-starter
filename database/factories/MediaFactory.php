<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'media/'.Str::random(40).'.png',
            'name' => fake()->word().'.png',
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1024, 500_000),
            'uploaded_by' => null,
        ];
    }

    public function pdf(): static
    {
        return $this->state(fn () => [
            'path' => 'media/'.Str::random(40).'.pdf',
            'name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}
