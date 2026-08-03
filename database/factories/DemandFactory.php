<?php

namespace Database\Factories;

use App\Models\Demand;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DemandFactory extends Factory
{
    protected $model = Demand::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'position' => 1,
        ];
    }
}
