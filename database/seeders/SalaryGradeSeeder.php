<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\SalaryGrade;
use Illuminate\Database\Seeder;

class SalaryGradeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Salary Grades (Janitor to Highest Paid)
        SalaryGrade::firstOrCreate(
            ['position_name' => 'Utility & Janitor'],
            ['min_salary' => 15000.00, 'max_salary' => 22000.00, 'annual_growth_rate' => 5.00]
        );

        SalaryGrade::firstOrCreate(
            ['position_name' => 'Fleet Driver (Senior & Junior)'],
            ['min_salary' => 18000.00, 'max_salary' => 35000.00, 'annual_growth_rate' => 6.00]
        );

        SalaryGrade::firstOrCreate(
            ['position_name' => 'Dispatch & Routing Specialist'],
            ['min_salary' => 25000.00, 'max_salary' => 45000.00, 'annual_growth_rate' => 7.00]
        );

        SalaryGrade::firstOrCreate(
            ['position_name' => 'Payroll & HR Specialist'],
            ['min_salary' => 28000.00, 'max_salary' => 55000.00, 'annual_growth_rate' => 7.50]
        );

        SalaryGrade::firstOrCreate(
            ['position_name' => 'Operations Manager'],
            ['min_salary' => 60000.00, 'max_salary' => 12000.00, 'annual_growth_rate' => 10.00]
        );

        // 2. Seed Client Dynamic Settings
        CompanySetting::firstOrCreate(
            ['key' => 'driver_incentive_trip_threshold'],
            ['value' => '20', 'description' => 'Minimum rides completed by driver to trigger cash incentive']
        );

        CompanySetting::firstOrCreate(
            ['key' => 'driver_incentive_reward_amount'],
            ['value' => '500.00', 'description' => 'Cash bonus amount rewarded per threshold reached']
        );

        CompanySetting::firstOrCreate(
            ['key' => 'hmo_employee_deduction_rate'],
            ['value' => '0.03', 'description' => 'Driver HMO employee payroll contribution rate (3%)']
        );
    }
}
