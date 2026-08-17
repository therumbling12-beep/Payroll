<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use Illuminate\Database\Seeder;

class SalaryGradeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Official PG-1 to PG-9 Pay Grades (known.md §6.2)
        $grades = [
            [
                'grade_code' => 'PG-1',
                'job_level' => 'Entry Level',
                'position_name' => 'Utility & Janitor',
                'sample_positions' => 'Utility Worker, Janitor, Messenger, Office Aide',
                'min_salary' => 19630.00, // Aligned with DOLE NCR-27 PHP 755/day floor
                'max_salary' => 20000.00,
                'annual_growth_rate' => 5.00,
                'effectivity_date' => now()->startOfYear(),
            ],
            [
                'grade_code' => 'PG-2',
                'job_level' => 'Junior',
                'position_name' => 'Junior Staff & Entry Driver',
                'sample_positions' => 'Data Encoder, Customer Service Rep, Entry-Level Driver',
                'min_salary' => 19630.00,
                'max_salary' => 24000.00,
                'annual_growth_rate' => 5.50,
                'effectivity_date' => now()->startOfYear(),
            ],
            [
                'grade_code' => 'PG-3',
                'job_level' => 'Intermediate',
                'position_name' => 'Dispatcher & Regular Driver',
                'sample_positions' => 'Operations Dispatcher, Billing Clerk, Regular TNVS Driver',
                'min_salary' => 20000.00,
                'max_salary' => 30000.00,
                'annual_growth_rate' => 6.00,
                'effectivity_date' => now()->startOfYear(),
            ],
            [
                'grade_code' => 'PG-4',
                'job_level' => 'Senior',
                'position_name' => 'Senior Staff & Senior Driver',
                'sample_positions' => 'Senior Dispatcher, Accounting Staff, Senior TNVS Driver',
                'min_salary' => 28000.00,
                'max_salary' => 40000.00,
                'annual_growth_rate' => 6.50,
                'effectivity_date' => now()->startOfYear(),
            ],
            [
                'grade_code' => 'PG-5',
                'job_level' => 'Supervisor',
                'position_name' => 'Fleet Supervisor',
                'sample_positions' => 'Fleet Supervisor, Payroll Supervisor, Senior Coordinator',
                'min_salary' => 38000.00,
                'max_salary' => 55000.00,
                'annual_growth_rate' => 7.00,
                'effectivity_date' => now()->startOfYear(),
            ],
            [
                'grade_code' => 'PG-6',
                'job_level' => 'Manager',
                'position_name' => 'Operations Manager',
                'sample_positions' => 'Operations Manager, Finance Manager, HR Manager',
                'min_salary' => 50000.00,
                'max_salary' => 80000.00,
                'annual_growth_rate' => 8.00,
                'effectivity_date' => now()->startOfYear(),
            ],
            [
                'grade_code' => 'PG-7',
                'job_level' => 'Senior Manager',
                'position_name' => 'Regional & Fleet Director',
                'sample_positions' => 'Regional Operations Manager, Fleet Director',
                'min_salary' => 75000.00,
                'max_salary' => 120000.00,
                'annual_growth_rate' => 9.00,
                'effectivity_date' => now()->startOfYear(),
            ],
            [
                'grade_code' => 'PG-8',
                'job_level' => 'Executive',
                'position_name' => 'Vice President & Deputies',
                'sample_positions' => 'VP Operations, Chief Operating Officer, Chief Financial Officer',
                'min_salary' => 120000.00,
                'max_salary' => 200000.00,
                'annual_growth_rate' => 10.00,
                'effectivity_date' => now()->startOfYear(),
            ],
            [
                'grade_code' => 'PG-9',
                'job_level' => 'C-Suite',
                'position_name' => 'Chief Executive & President',
                'sample_positions' => 'Chief Executive Officer, President, Managing Director',
                'min_salary' => 200000.00,
                'max_salary' => 350000.00,
                'annual_growth_rate' => 12.00,
                'effectivity_date' => now()->startOfYear(),
            ],
        ];

        // Standard Step Progression Definition (Section 2.12.1)
        $stepDefinitions = [
            ['step_number' => 1, 'years_required' => 0.0, 'increment_percentage' => 0.00],
            ['step_number' => 2, 'years_required' => 1.0, 'increment_percentage' => 3.00],
            ['step_number' => 3, 'years_required' => 2.0, 'increment_percentage' => 6.00],
            ['step_number' => 4, 'years_required' => 3.0, 'increment_percentage' => 11.00],
            ['step_number' => 5, 'years_required' => 5.0, 'increment_percentage' => 16.00],
            ['step_number' => 6, 'years_required' => 7.0, 'increment_percentage' => 23.00],
            ['step_number' => 7, 'years_required' => 10.0, 'increment_percentage' => 33.00],
        ];

        foreach ($grades as $gradeData) {
            $grade = SalaryGrade::updateOrCreate(
                ['position_name' => $gradeData['position_name']],
                $gradeData
            );

            // Seed steps 1 to 7 for this grade
            foreach ($stepDefinitions as $stepDef) {
                $base = $grade->min_salary;
                $pct = $stepDef['increment_percentage'];
                $baseAmount = $base + ($base * ($pct / 100));

                SalaryStep::updateOrCreate(
                    [
                        'salary_grade_id' => $grade->id,
                        'step_number' => $stepDef['step_number'],
                    ],
                    [
                        'years_required' => $stepDef['years_required'],
                        'increment_percentage' => $pct,
                        'base_amount' => round($baseAmount, 2),
                    ]
                );
            }
        }
    }
}
