<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\SalaryComputation;
use App\Models\TripIncome;
use Carbon\Carbon;

class PayrollTransparencyService
{
    public function __construct(
        protected SssContributionService $sssService,
        protected PhilHealthContributionService $philHealthService,
        protected PagIbigContributionService $pagIbigService,
        protected WithholdingTaxService $taxService
    ) {}

    /**
     * Generate a comprehensive, step-by-step mathematical breakdown for a SalaryComputation.
     *
     * @return array<string, mixed>
     */
    public function generateBreakdown(SalaryComputation $computation): array
    {
        $computation->loadMissing(['employee.department', 'employee.loans', 'loanAmortizationLogs.employeeLoan']);
        $employee = $computation->employee;

        $isDriver = $employee ? str_contains(strtolower($employee->position ?? ''), 'driver') : false;

        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $workingHours = (float) CompanySetting::getValue('standard_working_hours_per_day', 8.0);
        $platformFeeRate = (float) CompanySetting::getValue('driver_tnc_platform_fee_rate', 0.20);
        $driverHmoRate = (float) CompanySetting::getValue('driver_group_hmo_deduction_rate', 0.03);
        $regularMultiplier = (float) CompanySetting::getValue('holiday_regular_worked_multiplier', 2.00);
        $specialMultiplier = (float) CompanySetting::getValue('holiday_special_worked_multiplier', 1.30);
        $regularOtRate = (float) CompanySetting::getValue('overtime_regular_multiplier', 1.25);
        $nsdRate = (float) CompanySetting::getValue('night_shift_differential_rate', 0.10);

        // 1. Base Pay & Rate Conversion
        $monthlyRate = (float) ($employee?->monthly_rate ?: 0);
        $dailyRate = (float) ($employee?->daily_rate ?: ($monthlyRate > 0 ? round($monthlyRate / $workingDays, 2) : ($isDriver ? 1076.92 : 961.54)));
        $hourlyRate = round($dailyRate / $workingHours, 2);
        $minuteRate = round($hourlyRate / 60, 4);

        $basePay = (float) $computation->base_pay;
        $daysRendered = $dailyRate > 0 ? round($basePay / $dailyRate, 1) : 0.0;

        $basePayMath = [
            'monthly_rate' => $monthlyRate,
            'daily_rate' => $dailyRate,
            'hourly_rate' => $hourlyRate,
            'minute_rate' => $minuteRate,
            'days_rendered' => $daysRendered,
            'base_pay' => $basePay,
            'formula' => "PHP {$dailyRate} (Daily Rate) x {$daysRendered} Days = PHP " . number_format($basePay, 2),
        ];

        // 2. Attendance Timekeeping Deductions
        $tardinessDeduction = (float) $computation->tardiness_deduction;
        $undertimeDeduction = (float) $computation->undertime_deduction;
        $tardinessMinutes = $minuteRate > 0 ? (int) round($tardinessDeduction / $minuteRate) : 0;
        $undertimeMinutes = $minuteRate > 0 ? (int) round($undertimeDeduction / $minuteRate) : 0;

        $attendanceMath = [
            'minute_rate' => $minuteRate,
            'tardiness_minutes' => $tardinessMinutes,
            'tardiness_deduction' => $tardinessDeduction,
            'tardiness_formula' => "{$tardinessMinutes} mins x PHP {$minuteRate}/min = PHP " . number_format($tardinessDeduction, 2),
            'undertime_minutes' => $undertimeMinutes,
            'undertime_deduction' => $undertimeDeduction,
            'undertime_formula' => "{$undertimeMinutes} mins x PHP {$minuteRate}/min = PHP " . number_format($undertimeDeduction, 2),
            'total_attendance_deduction' => round($tardinessDeduction + $undertimeDeduction, 2),
        ];

        // 3. TNVS Fares, Commission & Quota Incentives
        $tripEarnings = (float) $computation->trip_earnings;
        $platformFee = (float) $computation->platform_fee_deduction;
        $driverTripIncentive = (float) ($computation->driver_trip_incentive ?? 0);

        $quotaTier = 'None';
        if ($driverTripIncentive >= 3000.00) {
            $quotaTier = 'Tier 3 (100+ Completed Bookings: PHP 3,000)';
        } elseif ($driverTripIncentive >= 1500.00) {
            $quotaTier = 'Tier 2 (80–99 Completed Bookings: PHP 1,500)';
        } elseif ($driverTripIncentive >= 500.00) {
            $quotaTier = 'Tier 1 (50–79 Completed Bookings: PHP 500)';
        }

        $platformFeePct = $platformFeeRate * 100;
        $tnvsMath = [
            'is_driver' => $isDriver,
            'trip_earnings' => $tripEarnings,
            'platform_fee_percent' => $platformFeePct,
            'platform_fee_deduction' => $platformFee,
            'platform_fee_formula' => "PHP {$tripEarnings} (Gross Fares) x {$platformFeePct}% TNC Commission = PHP " . number_format($platformFee, 2),
            'driver_trip_incentive' => $driverTripIncentive,
            'quota_tier_label' => $quotaTier,
        ];

        // 4. Holiday & Overtime Formulas
        $holidayPay = (float) $computation->holiday_pay;
        $overtimePay = (float) $computation->overtime_pay;
        $nightDiffPay = (float) $computation->night_diff_pay;

        $holidayOtMath = [
            'hourly_rate' => $hourlyRate,
            'holiday_pay' => $holidayPay,
            'regular_holiday_rate' => round($hourlyRate * $regularMultiplier, 2),
            'special_holiday_rate' => round($hourlyRate * $specialMultiplier, 2),
            'overtime_pay' => $overtimePay,
            'overtime_rate' => round($hourlyRate * $regularOtRate, 2),
            'night_diff_pay' => $nightDiffPay,
            'night_diff_rate' => round($hourlyRate * $nsdRate, 2),
            'performance_bonus' => (float) $computation->performance_bonus,
            'reimbursements' => (float) $computation->reimbursements,
            'total_gross' => (float) $computation->gross_pay,
        ];

        // 5. Statutory Contribution Table Lookups
        $monthlyBasis = max(10000.00, $employee?->monthly_rate ?: ((float) $computation->gross_pay * 2));
        $sssCalc = $this->sssService->compute($monthlyBasis, true);
        $philHealthCalc = $this->philHealthService->compute($monthlyBasis, true);
        $pagIbigCalc = $this->pagIbigService->compute($monthlyBasis, true);

        $statutoryLookups = [
            'monthly_basis' => $monthlyBasis,
            'sss' => [
                'msc' => $sssCalc['msc'],
                'ee_share' => (float) $computation->sss_deduction,
                'er_share' => (float) ($computation->sss_employer ?? $sssCalc['employer_share']),
                'ec_fund' => (float) ($computation->ec_contribution ?? $sssCalc['ec_contribution']),
                'table_reference' => 'SSS Circular No. 2024-006 (Total 14% = 9.5% ER + 4.5% EE)',
                'formula' => "MSC PHP " . number_format($sssCalc['msc'], 2) . " x 4.5% EE Share = PHP " . number_format((float) $computation->sss_deduction, 2),
            ],
            'philhealth' => [
                'mbs' => $monthlyBasis,
                'premium_rate' => 5.0,
                'ee_share' => (float) $computation->philhealth_deduction,
                'er_share' => (float) ($computation->philhealth_employer ?? $philHealthCalc['employer_share']),
                'table_reference' => 'PhilHealth Circular 2024 (5.0% Premium Split 50-50)',
                'formula' => "MBS PHP " . number_format($monthlyBasis, 2) . " x 5.0% / 2 (Semi-Monthly) = PHP " . number_format((float) $computation->philhealth_deduction, 2),
            ],
            'pagibig' => [
                'compensation' => $monthlyBasis,
                'ee_share' => (float) $computation->pagibig_deduction,
                'er_share' => (float) ($computation->pagibig_employer ?? $pagIbigCalc['employer_share']),
                'ceiling_applied' => true,
                'table_reference' => 'Pag-IBIG HDMF Circular No. 460 (PHP 100 EE / PHP 100 ER Semi-Monthly)',
                'formula' => "Statutory Fixed Share = PHP " . number_format((float) $computation->pagibig_deduction, 2),
            ],
            'driver_hmo' => [
                'rate' => $driverHmoRate * 100,
                'deduction' => (float) $computation->hmo_insurance_deduction,
                'formula' => $isDriver ? "Gross Earnings PHP " . number_format((float) $computation->gross_pay, 2) . " x " . ($driverHmoRate * 100) . "% Driver HMO = PHP " . number_format((float) $computation->hmo_insurance_deduction, 2) : "N/A",
            ],
        ];

        // 6. BIR Withholding Tax (TRAIN Law Graduated Table)
        $grossPay = (float) $computation->gross_pay;
        $statutoryExemptions = (float) $computation->sss_deduction + (float) $computation->philhealth_deduction + (float) $computation->pagibig_deduction + $tardinessDeduction + $undertimeDeduction;
        $taxableIncome = max(0.00, $grossPay - $statutoryExemptions);
        $withholdingTax = (float) $computation->withholding_tax;

        $trainBracket = 'Bracket 1 (Taxable Income <= PHP 10,417: PHP 0.00 / 0%)';
        $baseTax = 0.00;
        $marginalRate = 0.0;
        $excessOver = 0.00;

        if ($taxableIncome > 333333.00) {
            $trainBracket = 'Bracket 6 (> PHP 333,333: PHP 83,541.67 + 35%)';
            $baseTax = 83541.67;
            $marginalRate = 35.0;
            $excessOver = $taxableIncome - 333333.00;
        } elseif ($taxableIncome > 66667.00) {
            $trainBracket = 'Bracket 5 (PHP 66,667–333,333: PHP 14,583.33 + 30%)';
            $baseTax = 14583.33;
            $marginalRate = 30.0;
            $excessOver = $taxableIncome - 66667.00;
        } elseif ($taxableIncome > 33333.00) {
            $trainBracket = 'Bracket 4 (PHP 33,333–66,667: PHP 4,583.33 + 25%)';
            $baseTax = 4583.33;
            $marginalRate = 25.0;
            $excessOver = $taxableIncome - 33333.00;
        } elseif ($taxableIncome > 16667.00) {
            $trainBracket = 'Bracket 3 (PHP 16,667–33,333: PHP 1,250.00 + 20%)';
            $baseTax = 1250.00;
            $marginalRate = 20.0;
            $excessOver = $taxableIncome - 16667.00;
        } elseif ($taxableIncome > 10417.00) {
            $trainBracket = 'Bracket 2 (PHP 10,417–16,667: PHP 0.00 + 15%)';
            $baseTax = 0.00;
            $marginalRate = 15.0;
            $excessOver = $taxableIncome - 10417.00;
        }

        $taxMath = [
            'gross_pay' => $grossPay,
            'non_taxable_statutory_deductions' => $statutoryExemptions,
            'taxable_income' => $taxableIncome,
            'train_bracket' => $trainBracket,
            'base_tax' => $baseTax,
            'marginal_rate' => $marginalRate,
            'excess_over' => round($excessOver, 2),
            'withholding_tax' => $withholdingTax,
            'formula' => "Taxable Income (PHP " . number_format($taxableIncome, 2) . ") -> {$trainBracket} = PHP " . number_format($withholdingTax, 2),
        ];

        // 7. Loan Amortization Reductions
        $loanMath = [];
        if ($employee && $employee->loans) {
            foreach ($employee->loans as $loan) {
                $deduction = min((float) $loan->semi_monthly_amortization, (float) $loan->remaining_balance);
                $loanMath[] = [
                    'loan_type' => $loan->loan_type_label,
                    'reference_no' => $loan->reference_no,
                    'principal_amount' => (float) $loan->principal_amount,
                    'balance_before' => (float) $loan->remaining_balance,
                    'amortization_deduction' => $deduction,
                    'balance_after' => max(0.00, round((float) $loan->remaining_balance - $deduction, 2)),
                    'status' => $loan->status,
                ];
            }
        }

        // 8. Net Take-Home Pay Formula
        $totalDeductions = (float) $computation->total_deductions;
        $reimbursements = (float) $computation->reimbursements;
        $netPay = (float) $computation->net_pay;

        $netPayMath = [
            'gross_pay' => $grossPay,
            'total_deductions' => $totalDeductions,
            'reimbursements' => $reimbursements,
            'net_pay' => $netPay,
            'formula' => "Gross Pay (PHP " . number_format($grossPay, 2) . ") - Total Deductions (PHP " . number_format($totalDeductions, 2) . ") + Reimbursements (PHP " . number_format($reimbursements, 2) . ") = Net Pay (PHP " . number_format($netPay, 2) . ")",
        ];

        // 9. Employer Cost Burden Transparency
        $sssEr = (float) ($computation->sss_employer ?? $sssCalc['employer_share']);
        $philEr = (float) ($computation->philhealth_employer ?? $philHealthCalc['employer_share']);
        $pagEr = (float) ($computation->pagibig_employer ?? $pagIbigCalc['employer_share']);
        $ec = (float) ($computation->ec_contribution ?? 10.00);
        $totalEmployerCost = round($sssEr + $philEr + $pagEr + $ec, 2);

        $employerBurden = [
            'sss_employer' => $sssEr,
            'philhealth_employer' => $philEr,
            'pagibig_employer' => $pagEr,
            'ec_contribution' => $ec,
            'total_company_burden' => $totalEmployerCost,
            'formula' => "SSS ER (PHP {$sssEr}) + PhilHealth ER (PHP {$philEr}) + Pag-IBIG ER (PHP {$pagEr}) + EC (PHP {$ec}) = PHP " . number_format($totalEmployerCost, 2),
        ];

        return [
            'computation_id' => $computation->id,
            'cutoff_period' => $computation->cutoff_period,
            'employee' => [
                'id' => $employee?->id,
                'code' => $employee?->employee_code,
                'name' => ($employee?->first_name ?? '') . ' ' . ($employee?->last_name ?? ''),
                'position' => $employee?->position,
                'department' => $employee?->department?->name ?? 'General',
                'is_driver' => $isDriver,
            ],
            'base_pay_math' => $basePayMath,
            'attendance_math' => $attendanceMath,
            'tnvs_math' => $tnvsMath,
            'holiday_ot_math' => $holidayOtMath,
            'statutory_lookups' => $statutoryLookups,
            'tax_math' => $taxMath,
            'loan_math' => $loanMath,
            'net_pay_math' => $netPayMath,
            'employer_burden' => $employerBurden,
        ];
    }
}
