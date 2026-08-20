<?php

declare(strict_types=1);

namespace App\Services\Compensation;

use App\Models\CompanySetting;
use App\Models\CompensationAdjustment;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use Illuminate\Support\Facades\DB;

class TenureProgressionService
{
    public function __construct(
        protected CounterOfferService $counterOfferService
    ) {}

    public static function clearGradesCache(): void
    {
        if (app()->bound('tenure_grades_cache')) {
            app()->instance('tenure_grades_cache', null);
        }
    }

    /**
     * Compute Next Step Progression for an Employee along the 7-Step Ladder (known.md §6.5)
     *
     * @return array<string, mixed>
     */
    public function computeNextStep(Employee $employee): array
    {
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $driverDefault = (float) CompanySetting::getValue('driver_default_baseline_salary', 28000.00);
        $staffDefault = (float) CompanySetting::getValue('staff_default_baseline_salary', 25000.00);

        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
        $currentSalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * $workingDays : ($isDriver ? $driverDefault : $staffDefault)));

        if (app()->bound('tenure_grades_cache') && app('tenure_grades_cache') !== null) {
            $grades = app('tenure_grades_cache');
        } else {
            $grades = SalaryGrade::with('steps')->get();
            app()->instance('tenure_grades_cache', $grades);
        }

        $grade = $grades->firstWhere('position_name', $employee->position)
            ?? $grades->first(fn ($g) => $g->min_salary <= $currentSalary && $g->max_salary >= $currentSalary)
            ?? $grades->first();

        $currentStepNum = max(1, min(7, (int) ($employee->current_step ?? 1)));
        $nextStepNum = min(7, $currentStepNum + 1);

        $currentStepRecord = $grade ? $grade->steps->firstWhere('step_number', $currentStepNum) : null;
        $nextStepRecord = $grade ? $grade->steps->firstWhere('step_number', $nextStepNum) : null;

        $stepIncrementPct = (float) CompanySetting::getValue('tenure_step_default_increment_pct', 3.0);
        $defaultAllowance = (float) CompanySetting::getValue('standard_employee_allowance_monthly', 3500.00);

        $nextStepSalary = $nextStepRecord ? (float) $nextStepRecord->step_salary : round($currentSalary * (1 + ($stepIncrementPct / 100)), 2);
        if ($nextStepSalary <= $currentSalary && $currentStepNum < 7) {
            $nextStepSalary = round($currentSalary * (1 + ($stepIncrementPct / 100)), 2);
        }
        if ($currentStepNum >= 7) {
            $nextStepSalary = $currentSalary;
        }

        $incrementAmount = round(max(0.0, $nextStepSalary - $currentSalary), 2);
        $pct = $currentSalary > 0 ? round(($incrementAmount / $currentSalary) * 100, 2) : $stepIncrementPct;

        $oldCtc = $this->counterOfferService->calculateTotalCostToCompany($currentSalary, $defaultAllowance);
        $newCtc = $this->counterOfferService->calculateTotalCostToCompany($nextStepSalary, $defaultAllowance);

        return [
            'employee_id' => $employee->id,
            'employee_name' => "{$employee->first_name} {$employee->last_name}",
            'employee_code' => $employee->employee_code,
            'position' => $employee->position,
            'department' => $employee->department?->name ?? 'Operations',
            'years_of_service' => (float) ($employee->years_of_service ?? 1.0),
            'current_step' => $currentStepNum,
            'next_step' => $nextStepNum,
            'step_status' => $employee->step_status ?? 'normal',
            'step_hold_reason' => $employee->step_hold_reason,
            'current_salary' => $currentSalary,
            'next_step_salary' => $nextStepSalary,
            'increment_amount' => $incrementAmount,
            'increment_percentage' => $pct,
            'is_max_step' => $currentStepNum >= 7,
            'ctc_impact' => [
                'current_monthly_ctc' => $oldCtc['monthly_ctc'],
                'next_monthly_ctc' => $newCtc['monthly_ctc'],
                'incremental_monthly_ctc' => round($newCtc['monthly_ctc'] - $oldCtc['monthly_ctc'], 2),
                'incremental_annual_ctc' => round($newCtc['annual_ctc'] - $oldCtc['annual_ctc'], 2),
            ],
            'formula' => "Step {$currentStepNum} (PHP " . number_format($currentSalary, 2) . ") -> Step {$nextStepNum} (PHP " . number_format($nextStepSalary, 2) . ") = +PHP " . number_format($incrementAmount, 2),
        ];
    }

    /**
     * Apply Step Advancement to Employee
     */
    public function applyStepAdvance(
        Employee $employee,
        ?int $customStep = null,
        ?float $customRate = null,
        ?string $reason = null
    ): bool {
        return DB::transaction(function () use ($employee, $customStep, $customRate, $reason) {
            $calculation = $this->computeNextStep($employee);
            if (! $customStep && $calculation['is_max_step']) {
                return false;
            }

            $oldRate = (float) ($employee->monthly_rate ?: 0.0);
            $newRate = $customRate !== null ? $customRate : (float) $calculation['next_step_salary'];
            $nextStep = $customStep !== null ? $customStep : (int) $calculation['next_step'];

            $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
            $updateData = [
                'current_step' => $nextStep,
                'step_status' => 'normal',
                'step_hold_reason' => null,
                'monthly_rate' => $newRate,
            ];

            if ($isDriver) {
                $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
                $updateData['daily_rate'] = round($newRate / $workingDays, 2);
            }

            $employee->update($updateData);

            $defaultAllowance = (float) CompanySetting::getValue('standard_employee_allowance_monthly', 3500.00);
            $ctc = $this->counterOfferService->calculateTotalCostToCompany($newRate, $defaultAllowance);

            CompensationAdjustment::create([
                'employee_id' => $employee->id,
                'subject_type' => 'employee',
                'type' => 'step_increment',
                'mode' => 'mode_a',
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'monthly_ctc' => $ctc['monthly_ctc'],
                'annual_ctc' => $ctc['annual_ctc'],
                'thirteenth_month_liability' => $ctc['thirteenth_month_liability'],
                'employer_statutory_total' => $ctc['employer_statutory']['total'],
                'old_position' => $employee->position,
                'new_position' => $employee->position,
                'status' => 'approved',
                'budget_impact_status' => 'BUDGET_APPROVED',
                'reason' => $reason ?? "Applied Step {$nextStep} increment.",
                'effective_date' => now(),
            ]);

            PayrollAuditTrail::create([
                'action' => 'STEP_ADVANCEMENT_APPLIED',
                'model_type' => Employee::class,
                'model_id' => $employee->id,
                'user_name' => 'HR Manager',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['current_step' => $calculation['current_step'], 'monthly_rate' => $oldRate],
                'new_values' => ['current_step' => $nextStep, 'monthly_rate' => $newRate],
            ]);

            return true;
        });
    }

    /**
     * Freeze / Hold Step Advancement with Mandatory Business Justification
     */
    public function holdStepAdvance(Employee $employee, string $reason): bool
    {
        return DB::transaction(function () use ($employee, $reason) {
            $oldStatus = $employee->step_status;

            $employee->update([
                'step_status' => 'held',
                'step_hold_reason' => $reason,
            ]);

            PayrollAuditTrail::create([
                'action' => 'STEP_ADVANCEMENT_HELD',
                'model_type' => Employee::class,
                'model_id' => $employee->id,
                'user_name' => 'HR Manager',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['step_status' => $oldStatus],
                'new_values' => ['step_status' => 'held', 'step_hold_reason' => $reason],
            ]);

            return true;
        });
    }

    /**
     * Get Tenure Step Overview Grid and Eligible Candidate List.
     *
     * @return array<string, mixed>
     */
    public function getTenureStepOverview(): array
    {
        $salaryGrades = SalaryGrade::with('steps')->get();
        $employees = Employee::with('department')
            ->where('employment_status', '!=', 'resigned')
            ->get();

        $candidates = [];

        foreach ($employees as $emp) {
            $tenureYears = (float) $emp->years_of_service;
            $currentStep = (int) ($emp->current_step ?? 1);
            $grade = $salaryGrades->firstWhere('position_name', $emp->position);

            if (! $grade || $grade->steps->isEmpty()) {
                continue;
            }

            $steps = $grade->steps->sortBy('step_number');
            $nextStep = $steps->firstWhere('step_number', $currentStep + 1);

            $isEligible = $nextStep && ($tenureYears >= (float) $nextStep->years_required) && ($emp->step_status !== 'on_hold');

            $currentSalary = (float) ($emp->monthly_rate ?: ($emp->daily_rate ? $emp->daily_rate * 26 : 0.0));
            $nextSalary = $nextStep ? (float) $nextStep->step_salary : $currentSalary;

            $candidates[] = [
                'employee' => $emp,
                'salary_grade' => $grade,
                'current_step' => $currentStep,
                'target_step' => $nextStep ? $nextStep->step_number : ($currentStep + 1),
                'next_step' => $nextStep?->step_number,
                'tenure_years' => $tenureYears,
                'years_of_service' => $tenureYears,
                'tenure_text' => $emp->tenure_text,
                'next_step_years_required' => $nextStep?->years_required,
                'current_salary' => $currentSalary,
                'projected_salary' => round($nextSalary, 2),
                'is_eligible' => $isEligible,
                'step_status' => $emp->step_status ?? 'normal',
                'hold_reason' => $emp->step_hold_reason,
            ];
        }

        return [
            'salary_grades' => $salaryGrades,
            'candidates' => $candidates,
        ];
    }
}
