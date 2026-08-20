<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CompensationAdjustment;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class MeritPromotionSeeder extends Seeder
{
    /**
     * Run the database seeds with consistent mock data and backdated effectivity for retroactive pay testing.
     */
    public function run(): void
    {
        $employees = Employee::with('department')->get();

        if ($employees->isEmpty()) {
            return;
        }

        $scenarios = [
            'EMP-1001' => [
                'old_rate' => 32000.00,
                'new_rate' => 45000.00,
                'bonus' => 0.00,
                'status' => 'approved_by_team_3',
                'effective_date' => '2026-08-01',
                'reason' => 'Official Team 3 Approved Promotion Order #TM-2026-08 — Promoted to Senior HR Specialist.',
                'new_position' => 'Senior HR Specialist',
            ],
            'EMP-1002' => [
                'old_rate' => 26000.00,
                'new_rate' => 28000.00,
                'bonus' => 2500.00,
                'status' => 'approved',
                'effective_date' => '2026-05-01',
                'reason' => 'Market Pay Parity Adjustment — Competitive salary re-alignment for dispatch operations shift coverage.',
                'new_position' => 'Operations Dispatcher',
            ],
            'EMP-1003' => [
                'old_rate' => 38000.00,
                'new_rate' => 42000.00,
                'bonus' => 5000.00,
                'status' => 'approved',
                'effective_date' => '2026-06-01',
                'reason' => 'Promotion to Fleet Operations Supervisor — Promoted with proven dispatch fleet management leadership.',
                'new_position' => 'Fleet Supervisor',
            ],
            'EMP-1004' => [
                'old_rate' => 28000.00,
                'new_rate' => 30000.00,
                'bonus' => 0.00,
                'status' => 'pending',
                'effective_date' => '2026-07-01',
                'reason' => 'Annual Merit Review — Key contributions to payroll automation and benefits compliance.',
                'new_position' => 'Payroll Officer',
            ],
            'EMP-1005' => [
                'old_rate' => 20000.00,
                'new_rate' => 22000.00,
                'bonus' => 1500.00,
                'status' => 'approved',
                'effective_date' => '2026-06-01',
                'reason' => 'Performance Merit Increase — Exceptional support in administrative operations.',
                'new_position' => 'Admin Assistant',
            ],
        ];

        foreach ($employees as $employee) {
            if (isset($scenarios[$employee->employee_code])) {
                $sc = $scenarios[$employee->employee_code];

                CompensationAdjustment::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'type' => 'merit_promotion',
                    ],
                    [
                        'subject_type' => 'employee',
                        'old_rate' => $sc['old_rate'],
                        'new_rate' => $sc['new_rate'],
                        'bonus_amount' => $sc['bonus'],
                        'old_position' => $employee->position,
                        'new_position' => $sc['new_position'],
                        'reason' => $sc['reason'],
                        'status' => $sc['status'],
                        'effective_date' => $sc['effective_date'],
                    ]
                );
            }
        }
    }
}
