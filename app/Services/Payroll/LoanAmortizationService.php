<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanAmortizationLog;
use App\Models\SalaryComputation;
use Illuminate\Support\Facades\DB;

class LoanAmortizationService
{
    /**
     * Compute total active loan amortization deductions for an employee during payroll calculation.
     *
     * @return array{total_loan_deduction: float, breakdown: array<int, array<string, mixed>>}
     */
    public function compute(Employee $employee, string $cutoffPeriod): array
    {
        $activeLoans = EmployeeLoan::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->get();

        $totalDeduction = 0.00;
        $breakdown = [];

        foreach ($activeLoans as $loan) {
            $deductible = min((float) $loan->semi_monthly_amortization, (float) $loan->remaining_balance);
            if ($deductible > 0) {
                $totalDeduction += $deductible;
                $breakdown[] = [
                    'loan_id' => $loan->id,
                    'loan_type' => $loan->loan_type,
                    'reference_no' => $loan->reference_no,
                    'amortization' => $deductible,
                    'remaining_balance' => (float) $loan->remaining_balance,
                ];
            }
        }

        return [
            'total_loan_deduction' => round($totalDeduction, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Apply and record loan amortization deductions upon final payroll release.
     */
    public function applyDeductions(SalaryComputation $computation): void
    {
        DB::transaction(function () use ($computation) {
            $employee = $computation->employee;
            if (! $employee) {
                return;
            }

            $activeLoans = EmployeeLoan::where('employee_id', $employee->id)
                ->where('status', 'active')
                ->get();

            foreach ($activeLoans as $loan) {
                $deductible = min((float) $loan->semi_monthly_amortization, (float) $loan->remaining_balance);
                if ($deductible <= 0) {
                    continue;
                }

                $newBalance = max(0.00, round((float) $loan->remaining_balance - $deductible, 2));
                $newTotalPaid = round((float) $loan->total_paid + $deductible, 2);
                $newStatus = $newBalance <= 0.00 ? 'fully_paid' : 'active';

                $loan->update([
                    'remaining_balance' => $newBalance,
                    'total_paid' => $newTotalPaid,
                    'status' => $newStatus,
                ]);

                LoanAmortizationLog::create([
                    'employee_loan_id' => $loan->id,
                    'salary_computation_id' => $computation->id,
                    'cutoff_period' => $computation->cutoff_period,
                    'amount_deducted' => $deductible,
                    'remaining_balance_after' => $newBalance,
                    'deducted_at' => now(),
                ]);
            }
        });
    }
}
