<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\Claim;
use App\Models\Employee;
use App\Models\PerformanceBonus;
use App\Models\SalaryComputation;
use App\Models\TripIncome;
use Illuminate\Support\Facades\DB;

class PayrollEngineService
{
    /**
     * Compute salary for a specific employee & cutoff period
     */
    public function computeForEmployee(Employee $employee, string $cutoffPeriod): SalaryComputation
    {
        return DB::transaction(function () use ($employee, $cutoffPeriod) {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->first();

            $daysWorked = $attendance ? $attendance->days_worked : 10;
            $isDriver = str_contains($employee->position, 'Driver');

            $basePay = $isDriver
                ? 0.0
                : ((float) $employee->monthly_rate / 2);

            $tripIncome = TripIncome::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->first();
            $tripEarnings = $tripIncome ? (float) $tripIncome->total_trip_earnings : 0.0;

            $bonus = PerformanceBonus::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->first();
            $bonusAmount = $bonus ? (float) $bonus->bonus_amount : 0.0;

            // Fetch Approved Claims
            $approvedClaims = Claim::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->where('status', 'approved')
                ->get();

            // Taxable Driver Incentives
            $incentiveClaims = $approvedClaims->where('type', 'incentive')->sum('amount');
            $bonusAmount += (float) $incentiveClaims;

            // Non-Taxable Reimbursements (Expenses & Maternity Benefits)
            $reimbursements = (float) $approvedClaims->whereIn('type', ['expense', 'maternity'])->sum('amount');

            $grossPay = $basePay + $tripEarnings + $bonusAmount;

            $tnvsCommissionRate = (float) \App\Models\CompanySetting::getValue('tnvs_platform_commission_rate', 0.20);
            $sssRate = (float) \App\Models\CompanySetting::getValue('sss_deduction_rate', 0.05);
            $sssCap = (float) \App\Models\CompanySetting::getValue('sss_maximum_cap', 1750.00);
            $philhealthRate = (float) \App\Models\CompanySetting::getValue('philhealth_deduction_rate', 0.025);
            $philhealthCap = (float) \App\Models\CompanySetting::getValue('philhealth_maximum_cap', 2500.00);
            $pagibigFixed = (float) \App\Models\CompanySetting::getValue('pagibig_fixed_amount', 200.00);
            $birThreshold = (float) \App\Models\CompanySetting::getValue('bir_withholding_threshold', 20833.33);
            $birRate = (float) \App\Models\CompanySetting::getValue('bir_withholding_rate', 0.20);

            // Drivers are TNVS Contractors (No Employee Statutory Deductions, No HMO, 20% Platform Fee)
            $sss = $isDriver ? 0.00 : min($sssCap, round($grossPay * $sssRate, 2));
            $philhealth = $isDriver ? 0.00 : min($philhealthCap, round($grossPay * $philhealthRate, 2));
            $pagibig = $isDriver ? 0.00 : $pagibigFixed;
            $hmoInsuranceDeduction = 0.00;
            $platformFee = $isDriver ? round($grossPay * $tnvsCommissionRate, 2) : 0.00;

            // Taxable Base Calculation
            $taxableIncome = $isDriver ? 0.00 : max(0.00, $grossPay - ($sss + $philhealth + $pagibig));

            // BIR Withholding Tax
            $withholdingTax = ($isDriver || $taxableIncome <= $birThreshold)
                ? 0.00
                : round(($taxableIncome - $birThreshold) * $birRate, 2);

            $totalDeductions = $sss + $philhealth + $pagibig + $hmoInsuranceDeduction + $platformFee + $withholdingTax;

            // Non-taxable reimbursements added directly to net pay
            $netPay = ($grossPay - $totalDeductions) + $reimbursements;

            $computation = SalaryComputation::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'cutoff_period' => $cutoffPeriod,
                ],
                [
                    'base_pay' => $basePay,
                    'trip_earnings' => $tripEarnings,
                    'performance_bonus' => $bonusAmount,
                    'reimbursements' => $reimbursements,
                    'gross_pay' => $grossPay,
                    'sss_deduction' => $sss,
                    'philhealth_deduction' => $philhealth,
                    'pagibig_deduction' => $pagibig,
                    'hmo_insurance_deduction' => $hmoInsuranceDeduction,
                    'platform_fee_deduction' => $platformFee,
                    'withholding_tax' => $withholdingTax,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $netPay,
                    'status' => 'pending_approval',
                ]
            );

            // Automatically trigger Groq AI Compliance Evaluation
            app(GroqAiComplianceService::class)->analyzeCompliance($computation);

            return $computation;
        });
    }
}
