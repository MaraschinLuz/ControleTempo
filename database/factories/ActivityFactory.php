<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->word(), 'description' => fake()->sentence(), 'active' => true];
    }
}
