<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AccidentClaim;
use App\Models\BudgetRequisition;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoDependent;
use App\Models\HmoEnrollment;
use App\Models\HmoUtilizationLog;
use Illuminate\Database\Seeder;

class HmoLiveDemoSeeder extends Seeder
{
    /**
     * Seed live demonstration records for panel presentation.
     */
    public function run(): void
    {
        $fleetDept = Department::firstOrCreate(['name' => 'Fleet Operations']);
        $logisticsDept = Department::firstOrCreate(['name' => 'Logistics Operations']);

        // Demo Employee 1: Liza Cruz (Office Specialist with Low Health Balance)
        $emp1 = Employee::updateOrCreate(
            ['employee_code' => 'EMP-DEMO-01'],
            [
                'first_name' => 'Liza',
                'last_name' => 'Cruz',
                'email' => 'liza.cruz@tripease.com',
                'department_id' => $fleetDept->id,
                'position' => 'Senior Operations Specialist',
                'employment_status' => 'regular',
                'monthly_rate' => 38000.00,
                'daily_rate' => 1461.54,
                'hire_date' => now()->subYears(2),
            ]
        );

        $enrollment1 = HmoEnrollment::updateOrCreate(
            ['employee_id' => $emp1->id],
            [
                'hmo_card_number' => 'MAX-2026-DEMO-01',
                'hmo_provider' => 'Maxicare',
                'provider_plan' => 'Maxicare Plus Care',
                'coverage_tier' => 'Plus',
                'annual_limit' => 100000.00,
                'mbl_amount' => 100000.00,
                'monthly_premium' => 1800.00,
                'dependent_count' => 1,
                'coverage_start_date' => now()->startOfYear()->toDateString(),
                'coverage_end_date' => now()->endOfYear()->toDateString(),
                'status' => 'active',
                'enrollment_status' => 'active',
            ]
        );

        $depData = [
            'employee_id' => $emp1->id,
            'relationship' => 'Spouse',
            'birth_date' => '1990-04-12',
            'gender' => 'Male',
            'status' => 'verified',
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('hmo_dependents', 'first_name')) {
            $depData['first_name'] = 'Mark';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('hmo_dependents', 'last_name')) {
            $depData['last_name'] = 'Cruz';
        }

        HmoDependent::updateOrCreate(
            [
                'hmo_enrollment_id' => $enrollment1->id,
                'full_name' => 'Mark Cruz',
            ],
            $depData
        );

        // Utilization log of 85,000 (85% used, 15,000 left -> triggers <20% Low Balance Warning)
        HmoUtilizationLog::updateOrCreate(
            [
                'hmo_enrollment_id' => $enrollment1->id,
                'service_provider' => 'St. Luke\'s Medical Center Quezon City',
            ],
            [
                'employee_id' => $emp1->id,
                'benefit_type' => 'HMO — Inpatient Surgery & Hospitalization',
                'utilized_amount' => 85000.00,
                'remaining_balance' => 15000.00,
                'utilized_at' => now()->subMonths(1)->toDateString(),
                'description' => 'Laparoscopic procedure and post-op care.',
            ]
        );

        // Demo Employee 2: Jose Reyes (Driver with Accident Pool Claim)
        $emp2 = Employee::updateOrCreate(
            ['employee_code' => 'DRV-DEMO-02'],
            [
                'first_name' => 'Jose',
                'last_name' => 'Reyes',
                'email' => 'jose.reyes@tripease.com',
                'department_id' => $fleetDept->id,
                'position' => 'Senior TNVS Fleet Driver',
                'employment_status' => 'regular',
                'monthly_rate' => 26000.00,
                'daily_rate' => 1000.00,
                'hire_date' => now()->subYears(1),
            ]
        );

        AccidentClaim::updateOrCreate(
            ['incident_number' => 'CLM-2026-DEMO-01'],
            [
                'employee_id' => $emp2->id,
                'incident_type' => 'Vehicular Minor Collision & Outpatient Treatment',
                'incident_date' => now()->subDays(5)->toDateString(),
                'bill_amount' => 12500.00,
                'approved_amount' => 12500.00,
                'workflow_status' => 'pending_finance',
                'hr_status' => 'approved',
                'hr_reviewed_at' => now()->subDays(3),
                'hr_remarks' => 'Verified dashcam footage and official clinic receipts.',
                'admin_status' => 'approved',
                'admin_reviewed_at' => now()->subDays(1),
                'admin_remarks' => 'Approved under TNVS Driver Accident Protection Policy.',
                'finance_status' => 'pending',
                'description' => 'Motorcycle side-swipe during active delivery route. Minor contusions treated at Cardinal Santos.',
                'vehicle_plate_number' => 'NCS-8899',
            ]
        );

        // Demo Budget Requisitions
        BudgetRequisition::updateOrCreate(
            ['requisition_code' => 'REQ-2026-DEMO-01'],
            [
                'category' => 'HMO Healthcare Coverage',
                'amount' => 500000.00,
                'justification' => 'Annual Corporate Healthcare Premium Allocation for FY 2026.',
                'status' => 'approved',
            ]
        );

        BudgetRequisition::updateOrCreate(
            ['requisition_code' => 'REQ-2026-DEMO-02'],
            [
                'category' => 'Driver Accident Pool Subsidies',
                'amount' => 150000.00,
                'justification' => 'Corporate matching subsidy release for TNVS Driver Emergency Accident Pool.',
                'status' => 'released',
            ]
        );
    }
}
