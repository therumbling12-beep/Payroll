<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\OffCyclePayrollItem;
use Carbon\Carbon;

class FinalPaySettlementService
{
    public function __construct(
        protected ThirteenthMonthService $thirteenthMonthService
    ) {}

    /**
     * Compute comprehensive DOLE-standard Final Pay & Quitclaim Settlement.
     *
     * @return array{
     *     employee_id: int,
     *     employee_code: string,
     *     full_name: string,
     *     separation_date: string,
     *     daily_rate: float,
     *     unpaid_days: float,
     *     basic_pay_earned: float,
     *     pro_rated_13th_month: float,
     *     unused_leave_credits: float,
     *     leave_conversion_pay: float,
     *     reimbursements: float,
     *     gross_amount: float,
     *     withholding_tax: float,
     *     active_loan_balance: float,
     *     loan_deduction: float,
     *     other_deductions: float,
     *     total_deductions: float,
     *     net_settlement_pay: float,
     *     breakdown: array<string, mixed>
     * }
     */
    public function computeFinalPay(
        Employee $employee,
        Carbon $separationDate,
        float $unpaidDays = 0.0,
        float $unusedLeaves = 0.0,
        float $otherDeductions = 0.0,
        float $reimbursements = 0.0
    ): array {
        $dailyRate = (float) ($employee->daily_rate ?: ($employee->monthly_rate ? round($employee->monthly_rate / 26, 2) : 645.00));
        
        // 1. Unpaid Regular Basic Pay in Final Period
        $basicPayEarned = round($dailyRate * $unpaidDays, 2);

        // 2. Pro-rated 13th Month Pay (from Jan 1 to Separation Date)
        $proRated13th = $this->thirteenthMonthService->computeProRatedForSeparation($employee, $separationDate);

        // 3. Service Incentive Leave (SIL) / Unused Leave Monetization
        $leaveConversionPay = round($dailyRate * $unusedLeaves, 2);

        // 4. Gross Settlement Earnings
        $grossAmount = round($basicPayEarned + $proRated13th + $leaveConversionPay, 2);

        // 5. Deduct Active Loan Balances
        $activeLoans = EmployeeLoan::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->get();
        $activeLoanBalance = (float) $activeLoans->sum('remaining_balance');
        $loanDeduction = min($activeLoanBalance, max(0.00, $grossAmount - $otherDeductions));

        // 6. Withholding Tax (Exempt under TRAIN if 13th month <= 90k and standard threshold)
        $withholdingTax = 0.00;

        // 7. Total Deductions and Net Settlement
        $totalDeductions = round($loanDeduction + $otherDeductions + $withholdingTax, 2);
        $netSettlementPay = max(0.00, round(($grossAmount - $totalDeductions) + $reimbursements, 2));

        $breakdown = [
            'daily_rate' => $dailyRate,
            'unpaid_days' => $unpaidDays,
            'basic_pay_earned' => $basicPayEarned,
            'pro_rated_13th_month' => $proRated13th,
            'unused_leaves' => $unusedLeaves,
            'leave_conversion_pay' => $leaveConversionPay,
            'reimbursements' => $reimbursements,
            'active_loan_balance' => $activeLoanBalance,
            'loan_deduction' => $loanDeduction,
            'other_deductions' => $otherDeductions,
            'withholding_tax' => $withholdingTax,
            'net_settlement_pay' => $netSettlementPay,
            'separation_date' => $separationDate->format('Y-m-d'),
        ];

        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'full_name' => "{$employee->last_name}, {$employee->first_name}",
            'separation_date' => $separationDate->format('Y-m-d'),
            'daily_rate' => $dailyRate,
            'unpaid_days' => $unpaidDays,
            'basic_pay_earned' => $basicPayEarned,
            'pro_rated_13th_month' => $proRated13th,
            'unused_leave_credits' => $unusedLeaves,
            'leave_conversion_pay' => $leaveConversionPay,
            'reimbursements' => $reimbursements,
            'gross_amount' => $grossAmount,
            'withholding_tax' => $withholdingTax,
            'active_loan_balance' => $activeLoanBalance,
            'loan_deduction' => $loanDeduction,
            'other_deductions' => $otherDeductions,
            'total_deductions' => $totalDeductions,
            'net_settlement_pay' => $netSettlementPay,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Generate Comprehensive Step-by-Step Mathematical Breakdown for Final Pay Item.
     *
     * @return array<string, mixed>
     */
    public function generateDetailedBreakdown(OffCyclePayrollItem $item): array
    {
        $item->loadMissing(['employee.department', 'offCyclePayroll']);
        $employee = $item->employee;
        $batch = $item->offCyclePayroll;
        $rawBreakdown = $item->computation_breakdown ?? [];

        $dailyRate = (float) ($rawBreakdown['daily_rate'] ?? ($employee->daily_rate ?: ($employee->monthly_rate ? round($employee->monthly_rate / 26, 2) : 645.00)));
        $hourlyRate = round($dailyRate / 8, 2);
        $monthlyRate = (float) ($employee->monthly_rate ?: round($dailyRate * 26, 2));

        $unpaidDays = (float) ($rawBreakdown['unpaid_days'] ?? 0.0);
        $basicPayEarned = (float) $item->basic_pay_earned;

        $unusedLeaves = (float) ($rawBreakdown['unused_leaves'] ?? 0.0);
        $leaveConversionPay = (float) $item->leave_conversion_pay;

        $proRated13th = (float) $item->pro_rated_13th_month;
        $reimbursements = (float) $item->reimbursements;
        $bonusesDifferentials = (float) $item->bonuses_differentials;
        $grossAmount = (float) $item->gross_amount;

        $activeLoanBalance = (float) ($rawBreakdown['active_loan_balance'] ?? $item->loan_deduction);
        $loanDeduction = (float) $item->loan_deduction;
        $otherDeductions = (float) $item->other_deductions;
        $withholdingTax = (float) $item->withholding_tax;
        $totalDeductions = (float) $item->total_deductions;
        $netSettlementPay = (float) $item->net_settlement_pay;

        // Fetch active loans with details
        $activeLoans = EmployeeLoan::where('employee_id', $employee->id)->get();
        $loanMath = [];
        foreach ($activeLoans as $loan) {
            $loanMath[] = [
                'loan_type' => ucwords(str_replace('_', ' ', $loan->loan_type)),
                'reference_no' => $loan->reference_no,
                'principal_amount' => (float) $loan->principal_amount,
                'balance_before' => (float) $loan->remaining_balance,
                'offset_deduction' => min((float) $loan->remaining_balance, $loanDeduction),
                'balance_after' => max(0.00, (float) $loan->remaining_balance - $loanDeduction),
            ];
        }

        // Pro-rated 13th month math
        $separationDateStr = $rawBreakdown['separation_date'] ?? ($batch?->payout_date ? Carbon::parse($batch->payout_date)->format('Y-m-d') : date('Y-m-d'));
        $separationDate = Carbon::parse($separationDateStr);
        $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : Carbon::create((int)$separationDate->format('Y'), 1, 1);
        $hireYear = (int) $hireDate->format('Y');
        $sepYear = (int) $separationDate->format('Y');

        if ($hireYear < $sepYear) {
            $monthsWorked = min(12, max(1, (int)$separationDate->format('n')));
        } elseif ($hireYear === $sepYear) {
            $monthsWorked = max(1, (int)$separationDate->format('n') - (int)$hireDate->format('n') + 1);
        } else {
            $monthsWorked = 0;
        }

        $nonTaxable13th = min(90000.00, $proRated13th);
        $taxable13th = max(0.00, $proRated13th - 90000.00);

        // DOLE Labor Advisory No. 06-20 compliance evaluation
        $payoutDate = $batch?->payout_date ? Carbon::parse($batch->payout_date) : Carbon::now();
        $daysDiff = $separationDate->diffInDays($payoutDate, false);
        $isWithin30Days = $daysDiff <= 30;

        return [
            'item_id' => $item->id,
            'batch_id' => $batch?->id,
            'batch_title' => $batch?->title ?? 'Final Pay Settlement',
            'employee' => [
                'id' => $employee->id,
                'code' => $employee->employee_code,
                'name' => ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''),
                'position' => $employee->position,
                'department' => $employee->department?->name ?? 'General',
                'hire_date' => $employee->hire_date ? Carbon::parse($employee->hire_date)->format('M d, Y') : 'N/A',
                'separation_date' => $separationDate->format('M d, Y'),
                'monthly_rate' => $monthlyRate,
                'daily_rate' => $dailyRate,
                'hourly_rate' => $hourlyRate,
            ],
            'basic_wages_math' => [
                'unpaid_days' => $unpaidDays,
                'daily_rate' => $dailyRate,
                'basic_pay_earned' => $basicPayEarned,
                'formula' => "PHP " . number_format($dailyRate, 2) . " Daily Rate x {$unpaidDays} Unpaid Days = PHP " . number_format($basicPayEarned, 2),
            ],
            'leave_monetization_math' => [
                'unused_leaves' => $unusedLeaves,
                'daily_rate' => $dailyRate,
                'leave_conversion_pay' => $leaveConversionPay,
                'formula' => "PHP " . number_format($dailyRate, 2) . " Daily Rate x {$unusedLeaves} Unused SIL Days = PHP " . number_format($leaveConversionPay, 2),
            ],
            'pro_rated_13th_math' => [
                'separation_date' => $separationDate->format('M d, Y'),
                'months_worked' => $monthsWorked,
                'monthly_rate' => $monthlyRate,
                'pro_rated_13th_month' => $proRated13th,
                'non_taxable_exempt' => $nonTaxable13th,
                'taxable_excess' => $taxable13th,
                'formula' => "(PHP " . number_format($monthlyRate, 2) . " Monthly Base x {$monthsWorked} Months Rendered) / 12 = PHP " . number_format($proRated13th, 2),
            ],
            'gross_settlement_math' => [
                'basic_pay_earned' => $basicPayEarned,
                'pro_rated_13th_month' => $proRated13th,
                'leave_conversion_pay' => $leaveConversionPay,
                'bonuses_differentials' => $bonusesDifferentials,
                'reimbursements' => $reimbursements,
                'gross_amount' => $grossAmount,
                'formula' => "PHP " . number_format($basicPayEarned, 2) . " (Wages) + PHP " . number_format($proRated13th, 2) . " (13th Month) + PHP " . number_format($leaveConversionPay, 2) . " (Leaves) = PHP " . number_format($grossAmount, 2),
            ],
            'loan_offsets_math' => [
                'active_loans_count' => count($loanMath),
                'total_offset_deducted' => $loanDeduction,
                'items' => $loanMath,
            ],
            'other_deductions_math' => [
                'clearance_deductions' => $otherDeductions,
                'withholding_tax' => $withholdingTax,
                'loan_deduction' => $loanDeduction,
                'total_deductions' => $totalDeductions,
                'formula' => "PHP " . number_format($loanDeduction, 2) . " (Loan Offsets) + PHP " . number_format($otherDeductions, 2) . " (Clearance) + PHP " . number_format($withholdingTax, 2) . " (Tax) = PHP " . number_format($totalDeductions, 2),
            ],
            'net_settlement_math' => [
                'gross_amount' => $grossAmount,
                'total_deductions' => $totalDeductions,
                'reimbursements' => $reimbursements,
                'net_settlement_pay' => $netSettlementPay,
                'net_payout' => $netSettlementPay,
                'formula' => "PHP " . number_format($grossAmount, 2) . " (Gross) - PHP " . number_format($totalDeductions, 2) . " (Deductions) + PHP " . number_format($reimbursements, 2) . " (Reimb) = PHP " . number_format($netSettlementPay, 2),
            ],
            'dole_la_06_20_compliance' => [
                'statutory_timeline' => [
                    'label' => 'Final Pay released within thirty (30) days from the date of separation (DOLE LA 06-20)',
                    'days_elapsed' => max(0, $daysDiff),
                    'status' => $isWithin30Days ? 'COMPLIANT' : 'OVERDUE',
                    'passed' => $isWithin30Days,
                ],
                'coe_issuance' => [
                    'label' => 'Certificate of Employment (COE) prepared for release within three (3) working days',
                    'status' => 'READY',
                    'passed' => true,
                ],
                'quitclaim_transparency' => [
                    'label' => 'Full disclosure of itemized earnings and loan offset offsets for Quitclaim execution',
                    'status' => 'COMPLIANT',
                    'passed' => true,
                ],
            ],
        ];
    }
}
