<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => Activity::LOGIN,
            'causer_id' => User::factory(),
            'causer_name' => fake()->name(),
            'ip_address' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }

    /**
     * A model event against a subject, e.g. ->for a product that was created.
     */
    public function about(string $event, Model $subject): static
    {
        return $this->state(fn () => [
            'event' => $event,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'subject_label' => $subject->getAttribute('name') ?? $subject->getKey(),
        ]);
    }
}
