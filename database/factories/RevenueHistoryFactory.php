<?php

namespace Database\Factories;

use App\Models\Revenue;
use App\Models\RevenueHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevenueHistory>
 */
class RevenueHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'revenue_id' => Revenue::factory(),
            'changed_by' => User::factory(),
            'action' => 'updated',
            'changes' => ['name' => ['before' => 'Receita', 'after' => 'Receita atualizada']],
        ];
    }
}
