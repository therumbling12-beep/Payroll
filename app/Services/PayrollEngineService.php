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
                ? ((float) $employee->daily_rate * $daysWorked)
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

            // SSS Contribution: 4.5% capped at Monthly Salary Credit of P30,000 (max P1,350)
            $sss = min(1350.00, round($grossPay * 0.045, 2));

            // PhilHealth Contribution: 5% total split equally (2.5% employee share), capped at basic 100k salary (max P2,500)
            $philhealth = min(2500.00, round($grossPay * 0.025, 2));

            // Pag-IBIG HDMF fixed contribution
            $pagibig = 200.00;

            // Driver Insurance 3% deduction
            $hmoInsuranceDeduction = $isDriver ? round($grossPay * 0.03, 2) : 0.00;

            // Taxable Base Calculation
            $taxableIncome = max(0.00, $grossPay - ($sss + $philhealth + $pagibig));

            // BIR Withholding Tax MVP rule: 20% on taxable income above P20,833.33 cutoff limit
            $withholdingTax = ($taxableIncome > 20833.33)
                ? round(($taxableIncome - 20833.33) * 0.20, 2)
                : 0.00;

            $totalDeductions = $sss + $philhealth + $pagibig + $hmoInsuranceDeduction + $withholdingTax;

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
