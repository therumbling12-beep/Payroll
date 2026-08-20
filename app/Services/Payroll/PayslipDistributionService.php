<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use Illuminate\Support\Collection;

class PayslipDistributionService
{
    /**
     * Format comprehensive DOLE-compliant Payslip Data for a single SalaryComputation record.
     *
     * @return array{
     *     computation_id: int,
     *     cutoff_period: string,
     *     employee_id: int,
     *     employee_code: string,
     *     full_name: string,
     *     position: string,
     *     department: string,
     *     payment_mode: string,
     *     bank_name: string,
     *     bank_account_number: string,
     *     is_driver: bool,
     *     earnings: array{
     *         base_pay: float,
     *         trip_earnings: float,
     *         driver_trip_incentive: float,
     *         holiday_pay: float,
     *         overtime_pay: float,
     *         night_diff_pay: float,
     *         performance_bonus: float,
     *         reimbursements: float,
     *         gross_pay: float
     *     },
     *     deductions: array{
     *         sss_deduction: float,
     *         philhealth_deduction: float,
     *         pagibig_deduction: float,
     *         platform_fee_deduction: float,
     *         loan_deduction: float,
     *         withholding_tax: float,
     *         tardiness_deduction: float,
     *         undertime_deduction: float,
     *         total_deductions: float
     *     },
     *     employer_contributions: array{
     *         sss_employer: float,
     *         philhealth_employer: float,
     *         pagibig_employer: float,
     *         ec_contribution: float,
     *         total_employer_burden: float
     *     },
     *     net_pay: float,
     *     ytd: array{
     *         gross_pay: float,
     *         tax_withheld: float
     *     },
     *     itemized_loans: array<int, array<string, mixed>>
     * }
     */
    public function formatPayslipData(SalaryComputation $computation): array
    {
        $computation->loadMissing(['employee.department', 'employee.loans', 'loanAmortizationLogs.employeeLoan']);
        $emp = $computation->employee;

        $isDriver = $emp ? str_contains(strtolower($emp->position ?? ''), 'driver') : false;

        $grossPay = (float) $computation->gross_pay;
        $totalDeductions = (float) $computation->total_deductions;
        $netPay = (float) $computation->net_pay;

        $sssEr = (float) ($computation->sss_employer ?? $computation->sss_deduction * 2);
        $philEr = (float) ($computation->philhealth_employer ?? $computation->philhealth_deduction);
        $pagEr = (float) ($computation->pagibig_employer ?? $computation->pagibig_deduction);
        $ec = (float) ($computation->ec_contribution ?? 10.00);
        $totalEmployerBurden = round($sssEr + $philEr + $pagEr + $ec, 2);

        // YTD calculations for this employee up to this cutoff
        $year = substr($computation->cutoff_period, 0, 4);
        $ytdComps = SalaryComputation::where('employee_id', $computation->employee_id)
            ->where('cutoff_period', 'like', "{$year}-%")
            ->where('cutoff_period', '<=', $computation->cutoff_period)
            ->get();

        $ytdGross = (float) $ytdComps->sum('gross_pay');
        $ytdTax = (float) $ytdComps->sum('withholding_tax');

        // Itemized loans
        $itemizedLoans = [];
        if ($emp && $emp->loans) {
            foreach ($emp->loans as $loan) {
                $itemizedLoans[] = [
                    'loan_type' => $loan->loan_type_label,
                    'reference_no' => $loan->reference_no,
                    'semi_monthly_amortization' => (float) $loan->semi_monthly_amortization,
                    'remaining_balance' => (float) $loan->remaining_balance,
                    'status' => $loan->status,
                ];
            }
        }

        return [
            'computation_id' => $computation->id,
            'cutoff_period' => $computation->cutoff_period,
            'employee_id' => $emp?->id ?? 0,
            'employee_code' => $emp?->employee_code ?? 'N/A',
            'full_name' => ($emp?->last_name ?? '') . ', ' . ($emp?->first_name ?? ''),
            'position' => $emp?->position ?? 'N/A',
            'department' => $emp?->department?->name ?? 'General',
            'payment_mode' => $emp?->payment_mode ?? 'bank',
            'bank_name' => $emp?->bank_name ?? 'Security Bank Corporation',
            'bank_account_number' => $emp?->bank_account_number ?? $emp?->bank_account_no ?? 'N/A',
            'is_driver' => $isDriver,
            'earnings' => [
                'base_pay' => (float) $computation->base_pay,
                'trip_earnings' => (float) $computation->trip_earnings,
                'driver_trip_incentive' => (float) ($computation->driver_trip_incentive ?? 0),
                'holiday_pay' => (float) $computation->holiday_pay,
                'overtime_pay' => (float) $computation->overtime_pay,
                'night_diff_pay' => (float) $computation->night_diff_pay,
                'performance_bonus' => (float) $computation->performance_bonus,
                'reimbursements' => (float) $computation->reimbursements,
                'gross_pay' => $grossPay,
            ],
            'deductions' => [
                'sss_deduction' => (float) $computation->sss_deduction,
                'philhealth_deduction' => (float) $computation->philhealth_deduction,
                'pagibig_deduction' => (float) $computation->pagibig_deduction,
                'platform_fee_deduction' => (float) $computation->platform_fee_deduction,
                'loan_deduction' => (float) ($computation->loan_deduction ?? 0),
                'withholding_tax' => (float) $computation->withholding_tax,
                'tardiness_deduction' => (float) $computation->tardiness_deduction,
                'undertime_deduction' => (float) $computation->undertime_deduction,
                'total_deductions' => $totalDeductions,
            ],
            'employer_contributions' => [
                'sss_employer' => $sssEr,
                'philhealth_employer' => $philEr,
                'pagibig_employer' => $pagEr,
                'ec_contribution' => $ec,
                'total_employer_burden' => $totalEmployerBurden,
            ],
            'net_pay' => $netPay,
            'ytd' => [
                'gross_pay' => $ytdGross,
                'tax_withheld' => $ytdTax,
            ],
            'itemized_loans' => $itemizedLoans,
        ];
    }

    /**
     * Pull and format batch payslip collection for an entire cutoff.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function formatBatchPayslipData(string $cutoff, ?int $departmentId = null): Collection
    {
        $query = SalaryComputation::with(['employee.department', 'employee.loans', 'loanAmortizationLogs'])
            ->where('cutoff_period', $cutoff);

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $computations = $query->get();

        return $computations->map(fn(SalaryComputation $c) => $this->formatPayslipData($c));
    }
}
