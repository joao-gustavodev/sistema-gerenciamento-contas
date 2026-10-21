<?php

namespace Database\Factories;

use App\Models\Revenue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Revenue>
 */
class RevenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'amount_minor' => fake()->numberBetween(100, 1000000),
            'expected_date' => fake()->dateTimeBetween('now', '+3 months'),
            'source' => fake()->optional()->company(),
            'notes' => fake()->optional()->sentence(),
            'currency' => 'BRL',
            'status' => Revenue::STATUS_PENDING,
        ];
    }
}
