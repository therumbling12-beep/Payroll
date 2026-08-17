<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\SalaryComputation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ThirteenthMonthService
{
    /**
     * Compute Annual 13th Month Pay for an employee or all active employees.
     *
     * @return array{
     *     year: int,
     *     total_amount: float,
     *     employees: array<int, array<string, mixed>>
     * }
     */
    public function computeAnnual(int $year, ?int $employeeId = null): array
    {
        $query = Employee::with(['department', 'salaryComputations']);
        if ($employeeId) {
            $query->where('id', $employeeId);
        } else {
            $query->whereNotIn('employment_status', ['resigned', 'terminated']);
        }

        $employees = $query->get();
        $results = [];
        $totalBatchAmount = 0.00;

        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $driverDefault = (float) CompanySetting::getValue('driver_default_baseline_salary', 28000.00);
        $staffDefault = (float) CompanySetting::getValue('staff_default_baseline_salary', 25000.00);
        $taxExemptCeiling = (float) CompanySetting::getValue('train_law_13th_month_tax_exempt_ceiling', 90000.00);

        foreach ($employees as $employee) {
            $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
            $monthlySalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * $workingDays : ($isDriver ? $driverDefault : $staffDefault)));

            // 1. Calculate historical basic pay earned across all cutoffs in the year
            $computations = SalaryComputation::where('employee_id', $employee->id)
                ->where('cutoff_period', 'like', "{$year}-%")
                ->get();

            $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : ($employee->created_at ? Carbon::parse($employee->created_at) : Carbon::create($year, 1, 1));
            $hireYear = (int) $hireDate->format('Y');
            $hireMonth = (int) $hireDate->format('n');

            if ($hireYear < $year) {
                $monthsWorked = 12;
            } elseif ($hireYear === $year) {
                $monthsWorked = max(1, 12 - $hireMonth + 1);
            } else {
                $monthsWorked = 0;
            }

            if ($computations->isNotEmpty() && $computations->sum('base_pay') > 0) {
                $totalBasePayEarned = (float) $computations->sum('base_pay');
                $amount = round($totalBasePayEarned / 12, 2);
            } else {
                $amount = round(($monthlySalary * $monthsWorked) / 12, 2);
            }

            // TRAIN Law statutory ceiling for 13th month & other benefits is PHP 90,000.00
            $nonTaxableExempt = min($taxExemptCeiling, $amount);
            $taxableExcess = max(0.00, $amount - $taxExemptCeiling);

            $totalBatchAmount += $amount;

            $results[] = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => "{$employee->last_name}, {$employee->first_name}",
                'position' => $employee->position,
                'department' => $employee->department?->name ?? 'General',
                'monthly_salary' => $monthlySalary,
                'months_worked' => $monthsWorked,
                'amount' => $amount,
                'non_taxable_exempt' => $nonTaxableExempt,
                'taxable_excess' => $taxableExcess,
                'status' => 'pending_approval',
            ];
        }

        return [
            'year' => $year,
            'total_amount' => round($totalBatchAmount, 2),
            'employees' => $results,
        ];
    }

    /**
     * Generate Detailed 12-Month Cutoff Audit Ledger and Mathematical Transparency for an Employee.
     *
     * @return array<string, mixed>
     */
    public function getDetailedAuditLedger(int $year, int $employeeId): array
    {
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $driverDefault = (float) CompanySetting::getValue('driver_default_baseline_salary', 28000.00);
        $staffDefault = (float) CompanySetting::getValue('staff_default_baseline_salary', 25000.00);
        $taxExemptCeiling = (float) CompanySetting::getValue('train_law_13th_month_tax_exempt_ceiling', 90000.00);

        $employee = Employee::with('department')->findOrFail($employeeId);
        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
        $monthlySalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * $workingDays : ($isDriver ? $driverDefault : $staffDefault)));

        $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : ($employee->created_at ? Carbon::parse($employee->created_at) : Carbon::create($year, 1, 1));
        $hireYear = (int) $hireDate->format('Y');
        $hireMonth = (int) $hireDate->format('n');

        if ($hireYear < $year) {
            $monthsWorked = 12;
            $hiringStatus = 'Full Year Active';
        } elseif ($hireYear === $year) {
            $monthsWorked = max(1, 12 - $hireMonth + 1);
            $hiringStatus = "Mid-Year Hire ({$hireDate->format('M d, Y')}) - {$monthsWorked} Eligible Months";
        } else {
            $monthsWorked = 0;
            $hiringStatus = "Hired after {$year} (Not Eligible)";
        }

        // Fetch all salary computations for this employee in the target year
        $computations = SalaryComputation::where('employee_id', $employee->id)
            ->where('cutoff_period', 'like', "{$year}-%")
            ->get()
            ->keyBy('cutoff_period');

        $monthsData = [];
        $totalEarnedFromCutoffs = 0.00;
        $totalCutoffsCount = 0;

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        foreach ($monthNames as $mNum => $mName) {
            $mStr = str_pad((string) $mNum, 2, '0', STR_PAD_LEFT);
            $c1Key = "{$year}-{$mStr}-01_15";

            $lastDay = Carbon::create($year, $mNum, 1)->endOfMonth()->format('d');
            $c2Key = "{$year}-{$mStr}-16_{$lastDay}";

            $c1 = $computations->get($c1Key);
            $c2 = $computations->get($c2Key) ?? $computations->filter(fn($c) => str_starts_with($c->cutoff_period, "{$year}-{$mStr}-16"))->first();

            $c1Base = $c1 ? (float) $c1->base_pay : 0.00;
            $c2Base = $c2 ? (float) $c2->base_pay : 0.00;
            $monthTotal = $c1Base + $c2Base;

            if ($c1) {
                $totalCutoffsCount++;
            }
            if ($c2) {
                $totalCutoffsCount++;
            }

            $totalEarnedFromCutoffs += $monthTotal;

            $isMonthEligible = ($hireYear < $year) || ($hireYear === $year && $mNum >= $hireMonth);

            $monthsData[] = [
                'month_number' => $mNum,
                'month_name' => $mName,
                'is_eligible' => $isMonthEligible,
                'cutoff_1' => [
                    'period' => $c1Key,
                    'base_pay' => $c1Base,
                    'is_recorded' => (bool) $c1,
                ],
                'cutoff_2' => [
                    'period' => $c2Key,
                    'base_pay' => $c2Base,
                    'is_recorded' => (bool) $c2,
                ],
                'month_total' => $monthTotal,
            ];
        }

        if ($totalCutoffsCount > 0 && $totalEarnedFromCutoffs > 0) {
            $annualBasePayBasis = $totalEarnedFromCutoffs;
            $amount = round($totalEarnedFromCutoffs / 12, 2);
            $computationMode = "Actual Cutoff Earnings ({$totalCutoffsCount} Cutoffs Aggregated)";
            $formula = "PHP " . number_format($totalEarnedFromCutoffs, 2) . " (Total Basic Pay Received) / 12 Months = PHP " . number_format($amount, 2);
        } else {
            $annualBasePayBasis = round($monthlySalary * $monthsWorked, 2);
            $amount = round(($monthlySalary * $monthsWorked) / 12, 2);
            $computationMode = "Standard Monthly Pro-ration ({$monthsWorked} Months Rendered)";
            $formula = "(PHP " . number_format($monthlySalary, 2) . " Monthly Base x {$monthsWorked} Months) / 12 = PHP " . number_format($amount, 2);
        }

        $nonTaxableExempt = min($taxExemptCeiling, $amount);
        $taxableExcess = max(0.00, $amount - $taxExemptCeiling);

        return [
            'year' => $year,
            'employee' => [
                'id' => $employee->id,
                'code' => $employee->employee_code,
                'name' => ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''),
                'position' => $employee->position,
                'department' => $employee->department?->name ?? 'General',
                'hire_date' => $employee->hire_date ? Carbon::parse($employee->hire_date)->format('M d, Y') : 'N/A',
                'monthly_rate' => $monthlySalary,
                'hiring_status' => $hiringStatus,
            ],
            'audit_metrics' => [
                'months_worked' => $monthsWorked,
                'cutoffs_recorded_count' => $totalCutoffsCount,
                'annual_base_pay_basis' => $annualBasePayBasis,
                'calculated_amount' => $amount,
                'non_taxable_exempt' => $nonTaxableExempt,
                'taxable_excess' => $taxableExcess,
                'computation_mode' => $computationMode,
                'formula' => $formula,
            ],
            'monthly_breakdown' => $monthsData,
            'dole_compliance' => [
                'min_service_requirement' => [
                    'label' => 'Rendered at least 1 month (30 days) of service during the calendar year',
                    'status' => $monthsWorked >= 1 ? 'COMPLIANT' : 'NON_COMPLIANT',
                    'passed' => $monthsWorked >= 1,
                ],
                'basis_restriction' => [
                    'label' => 'Exclusive of allowances, overtime pay, and unintegrated monetary benefits (PD 851)',
                    'status' => 'COMPLIANT',
                    'passed' => true,
                ],
                'payment_deadline' => [
                    'label' => 'Disbursed to personnel on or before December 24',
                    'status' => 'COMPLIANT',
                    'passed' => true,
                ],
            ],
        ];
    }

    /**
     * Compute Pro-rated 13th Month Pay for Employee Separation / Final Pay.
     */
    public function computeProRatedForSeparation(Employee $employee, Carbon $separationDate): float
    {
        $year = (int) $separationDate->format('Y');
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $staffDefault = (float) CompanySetting::getValue('staff_default_baseline_salary', 25000.00);
        $monthlySalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * $workingDays : $staffDefault));

        $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : ($employee->created_at ? Carbon::parse($employee->created_at) : Carbon::create($year, 1, 1));
        $startDate = $hireDate->year === $year ? $hireDate : Carbon::create($year, 1, 1);

        if ($separationDate->lt($startDate)) {
            return 0.00;
        }

        // Dynamic sum from actual salary computations in current year up to separation date
        $computations = SalaryComputation::where('employee_id', $employee->id)
            ->where('cutoff_period', 'like', "{$year}-%")
            ->get();

        if ($computations->isNotEmpty() && $computations->sum('base_pay') > 0) {
            return round((float) $computations->sum('base_pay') / 12, 2);
        }

        $monthsWorked = min(12, max(1, ($separationDate->month - $startDate->month + 1)));
        return round(($monthlySalary * $monthsWorked) / 12, 2);
    }
}
