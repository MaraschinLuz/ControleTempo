<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        $start = now()->subDays(fake()->numberBetween(1, 20))->setTime(fake()->numberBetween(8, 15), 0);
        $seconds = fake()->numberBetween(1, 6) * 3600;

        return ['user_id' => User::factory(), 'project_id' => Project::factory(), 'activity_id' => Activity::factory(), 'description' => fake()->sentence(), 'started_at' => $start, 'ended_at' => $start->copy()->addSeconds($seconds), 'duration_seconds' => $seconds, 'entry_type' => 'timer', 'status' => 'completed', 'created_by' => fn (array $attributes) => $attributes['user_id'], 'is_edited' => false];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['entry_type' => 'manual', 'status' => 'pending', 'justification' => 'Lançamento realizado posteriormente.']);
    }
}
