<?php

declare(strict_types=1);

namespace App\Services\Compensation;

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;

class MeritIncreaseService
{
    public function __construct(
        protected CounterOfferService $counterOfferService
    ) {}

    /**
     * Compute 5-Tier Merit Increase Proposal for an Employee (known.md §6.7)
     *
     * Rating Scale & Matrix:
     * - 5.0 (Outstanding): 8.0% to 12.0% (Default: 10.0%)
     * - 4.0 - 4.9 (Very Satisfactory / Exceeds Expectations): 5.0% to 8.0% (Default: 6.5%)
     * - 3.0 - 3.9 (Satisfactory / Meets Expectations): 2.0% to 5.0% (Default: 3.5%)
     * - 2.0 - 2.9 (Needs Improvement): 0.0% to 2.0% (Default: 1.0%)
     * - < 2.0 (Unsatisfactory): 0.0% (Triggers PIP notice)
     *
     * @return array<string, mixed>
     */
    public function computeMeritIncrease(Employee $employee, ?float $customPercentage = null): array
    {
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $driverDefault = (float) CompanySetting::getValue('driver_default_baseline_salary', 28000.00);
        $staffDefault = (float) CompanySetting::getValue('staff_default_baseline_salary', 25000.00);
        $defaultAllowance = (float) CompanySetting::getValue('standard_employee_allowance_monthly', 3500.00);

        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
        $currentSalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * $workingDays : ($isDriver ? $driverDefault : $staffDefault)));

        $grade = SalaryGrade::where('position_name', $employee->position)->first()
            ?? SalaryGrade::first();

        $rating = $employee->performance_rating ?? 'Satisfactory';
        $score = $this->resolveScoreFromRating($rating);

        $matrixTier = $this->getMeritMatrixTier($score, $rating);
        $meritPercentage = $customPercentage !== null ? max(0.0, min(20.0, $customPercentage)) : $matrixTier['default_percentage'];

        $increaseAmount = round($currentSalary * ($meritPercentage / 100), 2);
        $gradeMax = $grade ? (float) $grade->max_salary : ($currentSalary * 1.5);

        $proposedSalary = min($gradeMax, round($currentSalary + $increaseAmount, 2));
        $actualIncrease = round($proposedSalary - $currentSalary, 2);
        $exceedsBandMax = ($currentSalary + $increaseAmount) > $gradeMax;

        $oldCtc = $this->counterOfferService->calculateTotalCostToCompany($currentSalary, $defaultAllowance);
        $newCtc = $this->counterOfferService->calculateTotalCostToCompany($proposedSalary, $defaultAllowance);

        $incrementalMonthlyCtc = round($newCtc['monthly_ctc'] - $oldCtc['monthly_ctc'], 2);
        $incrementalAnnualCtc = round($newCtc['annual_ctc'] - $oldCtc['annual_ctc'], 2);

        return [
            'employee_id' => $employee->id,
            'employee_name' => "{$employee->first_name} {$employee->last_name}",
            'employee_code' => $employee->employee_code,
            'position' => $employee->position,
            'department' => $employee->department?->name ?? 'General Operations',
            'performance_rating' => $rating,
            'performance_score' => $score,
            'matrix_tier' => $matrixTier['label'],
            'percentage_range' => $matrixTier['range'],
            'recommended_percentage' => $matrixTier['default_percentage'],
            'applied_percentage' => $meritPercentage,
            'current_salary' => $currentSalary,
            'increase_amount' => $actualIncrease,
            'proposed_salary' => $proposedSalary,
            'grade_max' => $gradeMax,
            'exceeds_band_max' => $exceedsBandMax,
            'pip_triggered' => $matrixTier['pip_triggered'],
            'ctc_impact' => [
                'current_monthly_ctc' => $oldCtc['monthly_ctc'],
                'new_monthly_ctc' => $newCtc['monthly_ctc'],
                'incremental_monthly_ctc' => $incrementalMonthlyCtc,
                'incremental_annual_ctc' => $incrementalAnnualCtc,
            ],
            'formula' => "PHP " . number_format($currentSalary, 2) . " + ({$meritPercentage}% Merit) = PHP " . number_format($proposedSalary, 2) . " (Annual CTC Impact: +PHP " . number_format($incrementalAnnualCtc, 2) . ")",
        ];
    }

    /**
     * Compute Promotion Grade Advancement Rule (known.md §6.4)
     *
     * Formula:
     * Promoted Salary = MAX(New Pay Grade Minimum, Current Salary x 1.15)
     *
     * @return array<string, mixed>
     */
    public function computePromotion(Employee $employee, SalaryGrade $newGrade): array
    {
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $driverDefault = (float) CompanySetting::getValue('driver_default_baseline_salary', 28000.00);
        $staffDefault = (float) CompanySetting::getValue('staff_default_baseline_salary', 25000.00);
        $defaultAllowance = (float) CompanySetting::getValue('standard_employee_allowance_monthly', 3500.00);
        $promotionMinPct = (float) CompanySetting::getValue('promotion_minimum_increase_pct', 15.0);

        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
        $currentSalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * $workingDays : ($isDriver ? $driverDefault : $staffDefault)));

        $newGradeMin = (float) $newGrade->min_salary;
        $newGradeMax = (float) $newGrade->max_salary;

        $fifteenPctBase = round($currentSalary * (1 + ($promotionMinPct / 100)), 2);
        $promotedSalary = max($newGradeMin, $fifteenPctBase);
        $promotedSalary = min($newGradeMax, $promotedSalary);

        $increaseAmount = round($promotedSalary - $currentSalary, 2);
        $actualPct = $currentSalary > 0 ? round(($increaseAmount / $currentSalary) * 100, 1) : $promotionMinPct;

        $oldCtc = $this->counterOfferService->calculateTotalCostToCompany($currentSalary, $defaultAllowance);
        $newCtc = $this->counterOfferService->calculateTotalCostToCompany($promotedSalary, $defaultAllowance);

        return [
            'employee_id' => $employee->id,
            'employee_name' => "{$employee->first_name} {$employee->last_name}",
            'old_position' => $employee->position,
            'new_position' => $newGrade->position_name,
            'new_grade_code' => $newGrade->grade_code,
            'new_job_level' => $newGrade->job_level,
            'new_grade_min' => $newGradeMin,
            'new_grade_max' => $newGradeMax,
            'current_salary' => $currentSalary,
            'fifteen_percent_floor' => $fifteenPctBase,
            'promoted_salary' => $promotedSalary,
            'increase_amount' => $increaseAmount,
            'increase_percentage' => $actualPct,
            'ctc_impact' => [
                'current_monthly_ctc' => $oldCtc['monthly_ctc'],
                'new_monthly_ctc' => $newCtc['monthly_ctc'],
                'incremental_monthly_ctc' => round($newCtc['monthly_ctc'] - $oldCtc['monthly_ctc'], 2),
                'incremental_annual_ctc' => round($newCtc['annual_ctc'] - $oldCtc['annual_ctc'], 2),
            ],
            'formula' => "Promoted Salary = MAX(New Grade Min PHP " . number_format($newGradeMin, 2) . ", Current x 1.15 PHP " . number_format($fifteenPctBase, 2) . ") = PHP " . number_format($promotedSalary, 2),
        ];
    }

    /**
     * Calculate Departmental Batch Summary for Financial Requisition (Team 5 Integration)
     *
     * @return array<string, mixed>
     */
    public function calculateDepartmentalSummary(int $departmentId): array
    {
        $department = Department::find($departmentId);
        $employees = Employee::where('department_id', $departmentId)
            ->where('employment_status', '!=', 'resigned')
            ->get();

        $totalHeadcount = $employees->count();
        $totalCurrentSalary = 0.0;
        $totalProposedSalary = 0.0;
        $totalMonthlyCtcIncrease = 0.0;
        $totalAnnualCtcIncrease = 0.0;

        foreach ($employees as $employee) {
            $merit = $this->computeMeritIncrease($employee);
            $totalCurrentSalary += $merit['current_salary'];
            $totalProposedSalary += $merit['proposed_salary'];
            $totalMonthlyCtcIncrease += $merit['ctc_impact']['incremental_monthly_ctc'];
            $totalAnnualCtcIncrease += $merit['ctc_impact']['incremental_annual_ctc'];
        }

        return [
            'department_id' => $departmentId,
            'department_name' => $department?->name ?? 'All Departments',
            'headcount' => $totalHeadcount,
            'total_current_salary' => round($totalCurrentSalary, 2),
            'total_proposed_salary' => round($totalProposedSalary, 2),
            'total_salary_increase' => round($totalProposedSalary - $totalCurrentSalary, 2),
            'total_monthly_ctc_increase' => round($totalMonthlyCtcIncrease, 2),
            'total_annual_ctc_increase' => round($totalAnnualCtcIncrease, 2),
            'requisition_status' => 'PENDING_FINANCE_VALIDATION',
        ];
    }

    protected function resolveScoreFromRating(string $rating): float
    {
        return match (strtolower(trim($rating))) {
            'outstanding', '5.0', '5' => 5.0,
            'very satisfactory', 'exceeds expectations', '4.0', '4.5', '4' => 4.5,
            'satisfactory', 'meets expectations', '3.0', '3.5', '3' => 3.5,
            'needs improvement', '2.0', '2.5', '2' => 2.5,
            'unsatisfactory', '1.0', '1.5', '1' => 1.5,
            default => 3.5,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function getMeritMatrixTier(float $score, string $rating): array
    {
        if ($score >= 5.0) {
            $pct = (float) CompanySetting::getValue('merit_tier_outstanding_pct', 10.0);
            return [
                'label' => '5.0 Outstanding',
                'range' => '8.0% - 12.0%',
                'default_percentage' => $pct,
                'pip_triggered' => false,
            ];
        }

        if ($score >= 4.0) {
            $pct = (float) CompanySetting::getValue('merit_tier_exceeds_pct', 6.5);
            return [
                'label' => '4.0 - 4.9 Exceeds Expectations',
                'range' => '5.0% - 8.0%',
                'default_percentage' => $pct,
                'pip_triggered' => false,
            ];
        }

        if ($score >= 3.0) {
            $pct = (float) CompanySetting::getValue('merit_tier_meets_pct', 3.5);
            return [
                'label' => '3.0 - 3.9 Meets Expectations',
                'range' => '2.0% - 5.0%',
                'default_percentage' => $pct,
                'pip_triggered' => false,
            ];
        }

        if ($score >= 2.0) {
            $pct = (float) CompanySetting::getValue('merit_tier_needs_improvement_pct', 1.0);
            return [
                'label' => '2.0 - 2.9 Needs Improvement',
                'range' => '0.0% - 2.0%',
                'default_percentage' => $pct,
                'pip_triggered' => false,
            ];
        }

        return [
            'label' => '< 2.0 Unsatisfactory',
            'range' => '0.0%',
            'default_percentage' => 0.0,
            'pip_triggered' => true,
        ];
    }
}
