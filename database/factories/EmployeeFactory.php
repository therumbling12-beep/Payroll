<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::inRandomOrder()->first()?->id ?? Department::factory(),
            'employee_code' => 'EMP-' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'position' => fake()->randomElement(['TNVS Senior Driver', 'TNVS Junior Driver', 'Operations Dispatcher', 'HR Specialist', 'Fleet Supervisor']),
            'daily_rate' => fake()->randomElement([600, 750, 850, 950]),
            'monthly_rate' => fake()->randomElement([25000, 30000, 35000, 45000]),
            'payment_mode' => fake()->randomElement(['cash', 'bank']),
            'bank_account_no' => 'BDO-' . fake()->numerify('##########'),
        ];
    }
}
