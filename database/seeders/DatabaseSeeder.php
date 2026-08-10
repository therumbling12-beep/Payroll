<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceBonus;
use App\Models\SalaryComputation;
use App\Models\TripIncome;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Seed Company Dynamic Settings Mock Data
        $settings = [
            'sss_deduction_rate' => 0.05,
            'sss_maximum_cap' => 1750.00,
            'philhealth_deduction_rate' => 0.025,
            'philhealth_maximum_cap' => 2500.00,
            'pagibig_fixed_amount' => 200.00,
            'hmo_driver_deduction_rate' => 0.03,
            'bir_withholding_threshold' => 20833.33,
            'bir_withholding_rate' => 0.20,
            'counter_offer_exp_multiplier' => 2500.00,
            'counter_offer_cert_multiplier' => 3500.00,
            'financial_budget_ceiling' => 150000.00,
            'maternity_leave_days' => 105,
            'standard_working_days_divisor' => 26,
            'ai_wage_safety_floor' => 755.00,
            'tnvs_platform_commission_rate' => 0.20,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\CompanySetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        // 1. Seed Departments
        $fleetDept = Department::create(['name' => 'Fleet Operations (Drivers)']);
        $dispatchDept = Department::create(['name' => 'Dispatch & Routing']);
        $adminDept = Department::create(['name' => 'Administration & HR']);

        // 2. Seed 30 Employees using Faker
        $employees = Employee::factory()->count(30)->create();

        // 3. For each employee, seed attendance, trip income, performance bonus, and calculated salary
        foreach ($employees as $employee) {
            $cutoff = '2026-07-01_15';
            $daysWorked = rand(8, 11);

            Attendance::create([
                'employee_id' => $employee->id,
                'cutoff_period' => $cutoff,
                'days_worked' => $daysWorked,
                'lates_count' => rand(0, 2),
            ]);

            $isDriver = str_contains($employee->position, 'Driver');
            $tripEarnings = $isDriver ? rand(20, 45) * 150 : 0;
            if ($isDriver) {
                TripIncome::create([
                    'employee_id' => $employee->id,
                    'cutoff_period' => $cutoff,
                    'total_trips' => rand(20, 45),
                    'total_trip_earnings' => $tripEarnings,
                ]);
            }

            $bonusAmount = rand(0, 1) === 1 ? 1000 : 0;
            if ($bonusAmount > 0) {
                PerformanceBonus::create([
                    'employee_id' => $employee->id,
                    'cutoff_period' => $cutoff,
                    'bonus_amount' => $bonusAmount,
                    'reason' => 'High performance rating',
                ]);
            }

            // Seed mock claims (Expense, Incentive, Maternity)
            \App\Models\Claim::create([
                'employee_id' => $employee->id,
                'type' => 'expense',
                'amount' => rand(300, 2500),
                'cutoff_period' => $cutoff,
                'description' => 'Vehicle Fuel & Toll Reimbursement',
                'receipt_number' => 'RCP-' . rand(10000, 99999),
                'status' => rand(0, 1) === 1 ? 'approved' : 'pending',
                'effective_date' => now(),
            ]);

            if ($isDriver) {
                \App\Models\Claim::create([
                    'employee_id' => $employee->id,
                    'type' => 'incentive',
                    'amount' => rand(1500, 5000),
                    'cutoff_period' => $cutoff,
                    'description' => 'Peak Hours High Efficiency Driver Incentive',
                    'receipt_number' => 'INC-' . rand(10000, 99999),
                    'status' => rand(0, 1) === 1 ? 'approved' : 'pending',
                    'effective_date' => now(),
                ]);
            }

            if (!$isDriver && rand(0, 3) === 1) {
                \App\Models\Claim::create([
                    'employee_id' => $employee->id,
                    'type' => 'maternity',
                    'amount' => 45000.00,
                    'cutoff_period' => $cutoff,
                    'description' => 'SSS Maternity Benefit Advance Claim',
                    'receipt_number' => 'MAT-' . rand(10000, 99999),
                    'status' => 'pending',
                    'effective_date' => now(),
                ]);
            }

            // Seed HMO Enrollments
            $hmoPlan = $isDriver ? 'InLife Fleet Protect' : (str_contains($employee->position, 'Manager') || str_contains($employee->position, 'Lead') ? 'Maxicard Gold' : 'Intellicare Silver');
            $mbl = $hmoPlan === 'Maxicard Gold' ? 250000 : ($hmoPlan === 'Intellicare Silver' ? 150000 : 100000);
            
            \App\Models\HmoEnrollment::create([
                'employee_id' => $employee->id,
                'hmo_card_number' => rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'provider_plan' => $hmoPlan,
                'mbl_amount' => $mbl,
                'status' => 'active',
            ]);

            // Seed Driver Accident Claims
            if ($isDriver && rand(0, 3) === 1) {
                \App\Models\AccidentClaim::create([
                    'employee_id' => $employee->id,
                    'incident_number' => 'INCIDENT-' . rand(1000, 9999),
                    'description' => 'Minor vehicle collision assistance during delivery',
                    'bill_amount' => rand(5000, 25000),
                    'status' => 'paid',
                ]);
            }

            $basePay = $isDriver ? 0.00 : ($employee->monthly_rate / 2);
            $grossPay = $basePay + $tripEarnings + $bonusAmount;

            $sss = $isDriver ? 0.00 : min(1750.00, round($grossPay * 0.05, 2));
            $philhealth = $isDriver ? 0.00 : min(2500.00, round($grossPay * 0.025, 2));
            $pagibig = $isDriver ? 0.00 : 200.00;
            $hmoDeduction = 0.00;
            $platformFee = $isDriver ? round($grossPay * 0.20, 2) : 0.00;
            
            $taxableIncome = $isDriver ? 0.00 : max(0.00, $grossPay - ($sss + $philhealth + $pagibig));
            $withholdingTax = $isDriver ? 0.00 : (($taxableIncome > 20833.33) ? round(($taxableIncome - 20833.33) * 0.20, 2) : 0.00);

            $totalDeductions = $sss + $philhealth + $pagibig + $hmoDeduction + $platformFee + $withholdingTax;
            $netPay = $grossPay - $totalDeductions;

            $comp = SalaryComputation::create([
                'employee_id' => $employee->id,
                'cutoff_period' => $cutoff,
                'base_pay' => $basePay,
                'trip_earnings' => $tripEarnings,
                'performance_bonus' => $bonusAmount,
                'gross_pay' => $grossPay,
                'sss_deduction' => $sss,
                'philhealth_deduction' => $philhealth,
                'pagibig_deduction' => $pagibig,
                'hmo_insurance_deduction' => $hmoDeduction,
                'platform_fee_deduction' => $platformFee,
                'withholding_tax' => $withholdingTax,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
                'status' => 'pending_approval',
            ]);

            app(\App\Services\GroqAiComplianceService::class)->analyzeCompliance($comp);
        }

        // Seed Financial Budget Requisitions
        \App\Models\BudgetRequisition::create([
            'requisition_code' => 'REQ-2026-081',
            'category' => 'Q3 HMO Provider Premiums',
            'amount' => 450000.00,
            'justification' => 'Annual corporate healthcare provider premium allocation for all staff.',
            'status' => 'approved',
        ]);

        \App\Models\BudgetRequisition::create([
            'requisition_code' => 'REQ-2026-094',
            'category' => 'Driver Accident Emergency Pool Top-Up',
            'amount' => 100000.00,
            'justification' => 'Emergency fund top-up for active driver fleet coverage.',
            'status' => 'awaiting_approval',
        ]);
    }
}
