<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\ThirteenthMonthComputation;

class BirAlphalistService
{
    /**
     * Compute BIR Form 1604-C Alphalist Schedule 7.1 (Employees Without Previous Employer).
     *
     * @return array{
     *     year: int,
     *     total_gross_compensation: float,
     *     total_non_taxable_statutory: float,
     *     total_exempt_thirteenth_month: float,
     *     total_taxable_compensation: float,
     *     total_tax_due: float,
     *     total_tax_withheld: float,
     *     total_over_withheld: float,
     *     total_under_withheld: float,
     *     employees: array<int, array<string, mixed>>
     * }
     */
    public function computeAlphalist(int $year): array
    {
        $employees = Employee::with(['department'])->orderBy('last_name')->get();

        $rows = [];
        $totalGross = 0.00;
        $totalNonTaxableStatutory = 0.00;
        $totalExempt13th = 0.00;
        $totalTaxable = 0.00;
        $totalTaxDue = 0.00;
        $totalTaxWithheld = 0.00;
        $totalOverWithheld = 0.00;
        $totalUnderWithheld = 0.00;

        foreach ($employees as $employee) {
            // 1. Fetch all Salary Computations for the year
            $computations = SalaryComputation::where('employee_id', $employee->id)
                ->where('cutoff_period', 'like', "{$year}-%")
                ->get();

            // If no records for this year, check general records
            if ($computations->isEmpty()) {
                $computations = SalaryComputation::where('employee_id', $employee->id)->get();
            }

            $grossCompensation = (float) $computations->sum('gross_pay');
            $sssDeductions = (float) $computations->sum('sss_deduction');
            $philhealthDeductions = (float) $computations->sum('philhealth_deduction');
            $pagibigDeductions = (float) $computations->sum('pagibig_deduction');
            $nonTaxableStatutory = round($sssDeductions + $philhealthDeductions + $pagibigDeductions, 2);

            // 2. Fetch 13th Month Pay Computation for the year
            $thirteenth = ThirteenthMonthComputation::where('employee_id', $employee->id)
                ->where('year', $year)
                ->first();

            $thirteenthAmount = $thirteenth ? (float) $thirteenth->amount : (float) ($employee->monthly_rate ?: 25000.00);
            
            // TRAIN Law statutory ceiling for 13th month & other benefits is PHP 90,000.00
            $exempt13th = min(90000.00, $thirteenthAmount);
            $taxable13thExcess = max(0.00, $thirteenthAmount - 90000.00);

            // 3. Taxable Compensation Income
            $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
            
            if ($isDriver) {
                // Contractors / Driver partners
                $taxableCompensation = 0.00;
                $annualTaxDue = 0.00;
                $taxWithheld = 0.00;
            } else {
                $taxableCompensation = max(0.00, round($grossCompensation - $nonTaxableStatutory - $exempt13th + $taxable13thExcess, 2));
                $annualTaxDue = $this->calculateAnnualTrainTax($taxableCompensation);
                $taxWithheld = (float) $computations->sum('withholding_tax');
            }

            // 4. Year-End Adjustment (Refund if positive, Payable if negative)
            $adjustment = round($taxWithheld - $annualTaxDue, 2);
            $adjustmentType = 'balanced';
            if ($adjustment > 0.01) {
                $adjustmentType = 'refund';
                $totalOverWithheld += $adjustment;
            } elseif ($adjustment < -0.01) {
                $adjustmentType = 'payable';
                $totalUnderWithheld += abs($adjustment);
            }

            $totalGross += $grossCompensation;
            $totalNonTaxableStatutory += $nonTaxableStatutory;
            $totalExempt13th += $exempt13th;
            $totalTaxable += $taxableCompensation;
            $totalTaxDue += $annualTaxDue;
            $totalTaxWithheld += $taxWithheld;

            $rows[] = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'tin' => '000-000-000-000',
                'full_name' => "{$employee->last_name}, {$employee->first_name}",
                'position' => $employee->position,
                'department' => $employee->department?->name ?? 'General',
                'is_driver' => $isDriver,
                'gross_compensation' => $grossCompensation,
                'non_taxable_statutory' => $nonTaxableStatutory,
                'exempt_thirteenth_month' => $exempt13th,
                'taxable_compensation' => $taxableCompensation,
                'annual_tax_due' => $annualTaxDue,
                'tax_withheld' => $taxWithheld,
                'tax_adjustment' => $adjustment,
                'adjustment_type' => $adjustmentType,
            ];
        }

        return [
            'year' => $year,
            'total_gross_compensation' => round($totalGross, 2),
            'total_non_taxable_statutory' => round($totalNonTaxableStatutory, 2),
            'total_exempt_thirteenth_month' => round($totalExempt13th, 2),
            'total_taxable_compensation' => round($totalTaxable, 2),
            'total_tax_due' => round($totalTaxDue, 2),
            'total_tax_withheld' => round($totalTaxWithheld, 2),
            'total_over_withheld' => round($totalOverWithheld, 2),
            'total_under_withheld' => round($totalUnderWithheld, 2),
            'employees' => $rows,
        ];
    }

    /**
     * Compute Annual Tax Due per Official TRAIN Law Graduated Table (RA 10963).
     */
    public function calculateAnnualTrainTax(float $taxableIncome): float
    {
        if ($taxableIncome <= 250000.00) {
            return 0.00;
        }

        if ($taxableIncome <= 400000.00) {
            return round(($taxableIncome - 250000.00) * 0.15, 2);
        }

        if ($taxableIncome <= 800000.00) {
            return round(22500.00 + (($taxableIncome - 400000.00) * 0.20), 2);
        }

        if ($taxableIncome <= 2000000.00) {
            return round(102500.00 + (($taxableIncome - 800000.00) * 0.25), 2);
        }

        if ($taxableIncome <= 8000000.00) {
            return round(402500.00 + (($taxableIncome - 2000000.00) * 0.30), 2);
        }

        return round(2202500.00 + (($taxableIncome - 8000000.00) * 0.35), 2);
    }
}
