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
use App\Services\Payroll\DriverTripIncentiveService;
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
        protected DriverTripIncentiveService $tripIncentiveService,
        protected LoanAmortizationService $loanService
    ) {}

    /**
     * Compute salary for a specific employee and cutoff period
     */
    public function computeForEmployee(Employee $employee, string $cutoffPeriod): SalaryComputation
    {
        return DB::transaction(function () use ($employee, $cutoffPeriod) {
            $defaultDaysWorked = (int) CompanySetting::getValue('payroll_default_semi_monthly_days_worked', 11);
            $defaultStaffBase = (float) CompanySetting::getValue('payroll_default_staff_semi_monthly_base', 12500.00);
            $statutoryMinBasis = (float) CompanySetting::getValue('statutory_minimum_monthly_basis', 10000.00);

            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->first();

            $daysWorked = $attendance ? $attendance->days_worked : $defaultDaysWorked;
            $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');

            // 1. Base Pay Computation (Semi-monthly: Monthly / 2 or Daily * Days Worked)
            if ($employee->monthly_rate > 0) {
                $basePay = round((float) $employee->monthly_rate / 2, 2);
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

            // 3. Driver Multi-Tier Trip Quota Incentives (Section 2.5)
            $incentiveResult = $this->tripIncentiveService->compute($employee, $cutoffPeriod);
            $driverTripIncentive = (float) $incentiveResult['incentive_amount'];

            // 4. Holiday Pay & Timekeeping Premiums (Labor Code Articles 87, 90, 93, 94)
            $holidayResult = $this->holidayService->compute($employee, $attendance, $cutoffPeriod);
            $holidayPay = (float) $holidayResult['holiday_pay'];

            $otResult = $this->overtimeService->compute($employee, $attendance);
            $overtimePay = (float) $otResult['overtime_pay'];
            $nightDiffPay = (float) $otResult['night_diff_pay'];

            // 5. Performance Bonuses & Approved Claims
            $bonus = PerformanceBonus::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->first();
            $bonusAmount = $bonus ? (float) $bonus->bonus_amount : 0.00;

            // Fetch Approved Claims
            $approvedClaims = Claim::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->where('status', 'approved')
                ->get();

            // Taxable Driver Incentives
            $incentiveClaims = (float) $approvedClaims->where('type', 'incentive')->sum('amount');
            $bonusAmount += $incentiveClaims;

            // Non-Taxable Reimbursements (Expenses & Maternity Benefits)
            $reimbursements = (float) $approvedClaims->whereIn('type', ['expense', 'maternity'])->sum('amount');

            // 6. Gross Pay
            $grossPay = round($basePay + $tripEarnings + $driverTripIncentive + $holidayPay + $overtimePay + $nightDiffPay + $bonusAmount, 2);

            // 7. Driver Platform Fees & HMO Deductions
            $platformFeeRate = (float) CompanySetting::getValue('driver_tnc_platform_fee_rate', 0.20);
            $driverHmoRate = (float) CompanySetting::getValue('driver_group_hmo_deduction_rate', 0.03);
            $platformFee = $isDriver ? round($tripEarnings * $platformFeeRate, 2) : 0.00; // TNC Commission
            $hmoInsuranceDeduction = $isDriver ? round($grossPay * $driverHmoRate, 2) : 0.00; // Driver Group Coverage

            // 8. Loan Amortization Deductions (SSS, Pag-IBIG, Company Loans)
            $loanResult = $this->loanService->compute($employee, $cutoffPeriod);
            $loanDeduction = (float) $loanResult['total_loan_deduction'];

            // 9. Tardiness & Undertime Deductions
            $tardyResult = $this->tardinessService->compute($employee, $attendance);
            $tardinessDeduction = (float) $tardyResult['tardiness_deduction'];
            $undertimeDeduction = (float) $tardyResult['undertime_deduction'];

            // 10. Statutory Deductions (2025-2026 Official Tables)
            $monthlyBasis = max($statutoryMinBasis, (float) ($employee->monthly_rate ?: ($grossPay * 2)));

            $sssResult = $this->sssService->compute($monthlyBasis, true);
            $philhealthResult = $this->philhealthService->compute($monthlyBasis, true);
            $pagibigResult = $this->pagibigService->compute($monthlyBasis, true);

            $sssEe = (float) $sssResult['employee_share'];
            $sssEr = (float) $sssResult['employer_share'];
            $ec = (float) $sssResult['ec_contribution'];

            $philhealthEe = (float) $philhealthResult['employee_share'];
            $philhealthEr = (float) $philhealthResult['employer_share'];

            $pagibigEe = (float) $pagibigResult['employee_share'];
            $pagibigEr = (float) $pagibigResult['employer_share'];

            // 11. Taxable Base & BIR Withholding Tax (TRAIN Law Graduated Brackets)
            $taxableIncome = max(0.00, $grossPay - ($sssEe + $philhealthEe + $pagibigEe + $tardinessDeduction + $undertimeDeduction));
            $withholdingTax = $this->taxService->compute($taxableIncome, true);

            // 12. Total Deductions & Net Take-Home Pay
            $totalDeductions = round(
                $sssEe + $philhealthEe + $pagibigEe + $hmoInsuranceDeduction + $platformFee + $loanDeduction + $withholdingTax + $tardinessDeduction + $undertimeDeduction,
                2
            );
            $netPay = round(($grossPay - $totalDeductions) + $reimbursements, 2);

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
                    'philhealth_employer' => $philRes['employer_share'] ?? $philhealthEr,
                    'pagibig_deduction' => $pagibigEe,
                    'pagibig_employer' => $pagibigEr,
                    'ec_contribution' => $ec,
                    'hmo_insurance_deduction' => $hmoInsuranceDeduction,
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
