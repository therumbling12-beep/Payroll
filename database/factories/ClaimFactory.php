<?php

namespace Database\Factories;

use App\Models\Claim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['expense', 'incentive', 'maternity']),
            'amount' => fake()->randomFloat(2, 500, 15000),
            'cutoff_period' => '2026-07-01_15',
            'description' => fake()->sentence(),
            'receipt_number' => 'RCP-' . fake()->unique()->numerify('#####'),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'effective_date' => now(),
        ];
    }
}
