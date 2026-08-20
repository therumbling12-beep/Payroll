<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\Claim;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\PerformanceBonus;
use App\Models\SalaryComputation;
use App\Models\TripIncome;
use App\Services\Payroll\HolidayPayService;
use App\Services\Payroll\LoanAmortizationService;
use App\Services\Payroll\OvertimePayService;
use App\Services\Payroll\PagIbigContributionService;
use App\Services\Payroll\PhilHealthContributionService;
use App\Services\Payroll\SssContributionService;
use App\Services\Payroll\TardinessDeductionService;
use App\Services\Payroll\WithholdingTaxService;
use Illuminate\Support\Facades\DB;

class PayrollEngineService
{
    public function __construct(
        protected SssContributionService $sssService,
        protected PhilHealthContributionService $philhealthService,
        protected PagIbigContributionService $pagibigService,
        protected WithholdingTaxService $taxService,
        protected HolidayPayService $holidayService,
        protected OvertimePayService $overtimeService,
        protected TardinessDeductionService $tardinessService,
        protected LoanAmortizationService $loanService
    ) {}

    /**
     * Compute salary for a specific employee and cutoff period
     */
    public function computeForEmployee(Employee $employee, string $cutoffPeriod): SalaryComputation
    {
        return DB::transaction(function () use ($employee, $cutoffPeriod) {
            $frequencySetting = (string) CompanySetting::getValue('payroll_frequency', 'weekly');
            $isSemiMonthlyCutoff = str_ends_with($cutoffPeriod, '_15') || str_ends_with($cutoffPeriod, '_30') || str_ends_with($cutoffPeriod, '_31');
            $isWeekly = (! $isSemiMonthlyCutoff) || ($frequencySetting === 'weekly' && ! $isSemiMonthlyCutoff);

            if (! $isWeekly) {
                $defaultDaysWorked = (int) CompanySetting::getValue('payroll_default_semi_monthly_days_worked', 11);
                $defaultStaffBase = (float) CompanySetting::getValue('payroll_default_staff_semi_monthly_base', 12500.00);
            } else {
                $defaultDaysWorked = (int) CompanySetting::getValue('payroll_default_weekly_days_worked', 6);
                $defaultStaffBase = (float) CompanySetting::getValue('payroll_default_staff_weekly_base', 5769.23);
            }

            $statutoryMinBasis = (float) CompanySetting::getValue('statutory_minimum_monthly_basis', 10000.00);

            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->first();

            $daysWorked = $attendance ? $attendance->days_worked : $defaultDaysWorked;
            $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');

            // 1. Base Pay Computation (Weekly: (Monthly * 12) / 52 or Daily * Days Worked; Semi-monthly: Monthly / 2)
            if ($employee->monthly_rate > 0) {
                if (! $isWeekly) {
                    $basePay = round((float) $employee->monthly_rate / 2, 2);
                } else {
                    $weeksPerYear = (float) CompanySetting::getValue('payroll_standard_weeks_per_year', 52.0);
                    $basePay = round(((float) $employee->monthly_rate * 12) / $weeksPerYear, 2);
                }
            } elseif ($employee->daily_rate > 0) {
                $basePay = round((float) $employee->daily_rate * $daysWorked, 2);
            } else {
                $basePay = $isDriver ? 0.00 : $defaultStaffBase;
            }

            // 2. Variable Trip Income (Fleet / Driver Trips)
            $tripIncome = TripIncome::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->first();
            $tripEarnings = $tripIncome ? (float) $tripIncome->total_trip_earnings : 0.00;

            // 3. Driver Multi-Tier Trip Quota Incentives (docs/no.md Lines 87, 91, 214 - Client does not use incentives/bonuses in payroll)
            $driverTripIncentive = 0.00;

            // 4. Holiday Pay & Timekeeping Premiums (Labor Code Articles 87, 90, 93, 94)
            $holidayResult = $this->holidayService->compute($employee, $attendance, $cutoffPeriod);
            $holidayPay = (float) $holidayResult['holiday_pay'];

            $otResult = $this->overtimeService->compute($employee, $attendance);
            $overtimePay = (float) $otResult['overtime_pay'];
            $nightDiffPay = (float) $otResult['night_diff_pay'];

            // 5. Performance Bonuses & Approved Claims (docs/no.md: Lines 87, 214)
            $includeDiscretionary = (bool) CompanySetting::getValue('payroll_include_discretionary_bonuses', false);
            $bonus = $includeDiscretionary
                ? PerformanceBonus::where('employee_id', $employee->id)->where('cutoff_period', $cutoffPeriod)->first()
                : null;
            $bonusAmount = $bonus ? (float) $bonus->bonus_amount : 0.00;

            // Fetch Approved Claims
            $approvedClaims = Claim::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->whereIn('approval_status', ['approved', 'payroll_queued'])
                ->get();

            // Non-Taxable Reimbursements (Expenses & Maternity Benefits) - Cash Settlement (docs/no.md: Line 200)
            $reimbursements = (float) $approvedClaims->whereIn('type', ['expense', 'maternity'])->sum('amount');

            // 6. Gross Pay (docs/no.md: Base Pay + Trips + Holiday + OT + NSD)
            $grossPay = round($basePay + $tripEarnings + $driverTripIncentive + $holidayPay + $overtimePay + $nightDiffPay + $bonusAmount, 2);

            // 7. Driver Platform Fees (TNC Commission)
            $platformFeeRate = (float) CompanySetting::getValue('driver_tnc_platform_fee_rate', 0.20);
            $platformFee = $isDriver ? round($tripEarnings * $platformFeeRate, 2) : 0.00; // TNC Commission

            // 8. Loan Amortization Deductions (SSS, Pag-IBIG, Company Loans)
            $loanResult = $this->loanService->compute($employee, $cutoffPeriod);
            $loanDeduction = (float) $loanResult['total_loan_deduction'];

            // 9. Tardiness & Undertime Deductions
            $tardyResult = $this->tardinessService->compute($employee, $attendance);
            $tardinessDeduction = (float) $tardyResult['tardiness_deduction'];
            $undertimeDeduction = (float) $tardyResult['undertime_deduction'];

            // 10. Statutory Deductions (2025-2026 Official Tables - Identical Parity for Staff and Drivers)
            $workingDaysPerMonth = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
            if ($employee->monthly_rate > 0) {
                $monthlyBasis = (float) $employee->monthly_rate;
            } elseif ($employee->daily_rate > 0) {
                $monthlyBasis = round((float) $employee->daily_rate * $workingDaysPerMonth, 2);
            } else {
                $monthlyBasis = max($statutoryMinBasis, (float) ($grossPay * ($isWeekly ? 4.33 : 2)));
            }

            $sssResult = $this->sssService->compute($monthlyBasis, false, true);
            $philhealthResult = $this->philhealthService->compute($monthlyBasis, false, true);
            $pagibigResult = $this->pagibigService->compute(
                $monthlyBasis,
                false,
                (float) ($employee->pagibig_voluntary_contribution ?? 0.0),
                true
            );

            $sssEe = (float) $sssResult['employee_share'];
            $sssEr = (float) $sssResult['employer_share'];
            $ec = (float) $sssResult['ec_contribution'];

            $philhealthEe = (float) $philhealthResult['employee_share'];
            $philhealthEr = (float) $philhealthResult['employer_share'];

            $pagibigEe = (float) $pagibigResult['employee_share'];
            $pagibigEr = (float) $pagibigResult['employer_share'];

            // 11. Taxable Base & BIR Withholding Tax (TRAIN Law Graduated Brackets)
            $taxableIncome = max(0.00, $grossPay - ($sssEe + $philhealthEe + $pagibigEe + $tardinessDeduction + $undertimeDeduction));
            $withholdingTax = $this->taxService->compute($taxableIncome, false, true);

            // 12. Total Deductions & Net Take-Home Pay (Strictly Gross minus Deductions; Cash Reimbursements settled separately)
            $totalDeductions = round(
                $sssEe + $philhealthEe + $pagibigEe + $platformFee + $loanDeduction + $withholdingTax + $tardinessDeduction + $undertimeDeduction,
                2
            );
            $netPay = round($grossPay - $totalDeductions, 2);

            $computation = SalaryComputation::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'cutoff_period' => $cutoffPeriod,
                ],
                [
                    'base_pay' => $basePay,
                    'trip_earnings' => $tripEarnings,
                    'driver_trip_incentive' => $driverTripIncentive,
                    'holiday_pay' => $holidayPay,
                    'overtime_pay' => $overtimePay,
                    'night_diff_pay' => $nightDiffPay,
                    'performance_bonus' => $bonusAmount,
                    'reimbursements' => $reimbursements,
                    'gross_pay' => $grossPay,
                    'sss_deduction' => $sssEe,
                    'sss_employer' => $sssEr,
                    'philhealth_deduction' => $philhealthEe,
                    'philhealth_employer' => $philhealthEr,
                    'pagibig_deduction' => $pagibigEe,
                    'pagibig_employer' => $pagibigEr,
                    'ec_contribution' => $ec,
                    'platform_fee_deduction' => $platformFee,
                    'loan_deduction' => $loanDeduction,
                    'withholding_tax' => $withholdingTax,
                    'tardiness_deduction' => $tardinessDeduction,
                    'undertime_deduction' => $undertimeDeduction,
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
