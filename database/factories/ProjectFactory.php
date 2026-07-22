<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return ['client_id' => Client::factory(), 'name' => fake()->unique()->words(3, true), 'description' => fake()->sentence(), 'status' => 'active', 'estimated_hours' => fake()->numberBetween(20, 200), 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(2)];
    }
}
