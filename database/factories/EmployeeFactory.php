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
        $status = fake()->randomElement(['regular', 'regular', 'regular', 'probationary']);
        $hireDate = $status === 'probationary'
            ? fake()->dateTimeBetween('-5 months', '-1 month')
            : fake()->dateTimeBetween('-5 years', '-6 months');

        $regularizationDate = $status === 'regular'
            ? (clone $hireDate)->modify('+6 months')
            : null;

        return [
            'department_id' => Department::inRandomOrder()->first()?->id ?? Department::factory(),
            'employee_code' => 'EMP-' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'position' => fake()->randomElement(['TNVS Senior Driver', 'TNVS Junior Driver', 'Operations Dispatcher', 'HR Specialist', 'Fleet Supervisor']),
            'hire_date' => $hireDate->format('Y-m-d'),
            'regularization_date' => $regularizationDate ? $regularizationDate->format('Y-m-d') : null,
            'employment_status' => $status,
            'performance_rating' => fake()->randomElement(['Outstanding', 'Very Satisfactory', 'Satisfactory', 'Needs Improvement']),
            'daily_rate' => fake()->randomElement([600, 750, 850, 950]),
            'monthly_rate' => fake()->randomElement([25000, 30000, 35000, 45000]),
            'current_step' => fake()->numberBetween(1, 3),
            'step_status' => 'normal',
            'payment_mode' => fake()->randomElement(['cash', 'bank']),
            'bank_name' => 'Security Bank Corporation',
            'bank_account_number' => fake()->numerify('0012345678'),
            'bank_account_no' => 'SBC-' . fake()->numerify('##########'),
        ];
    }
}
