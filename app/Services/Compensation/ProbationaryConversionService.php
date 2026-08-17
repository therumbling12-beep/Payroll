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

class ProbationaryConversionService
{
    public function __construct(
        protected CounterOfferService $counterOfferService
    ) {}

    /**
     * Evaluate 6-Month Probationary Incumbent Status against DOLE Art. 296 (known.md §6.8)
     *
     * @return array<string, mixed>
     */
    public function evaluateProbationaryStatus(Employee $employee): array
    {
        $maxProbationDays = (int) CompanySetting::getValue('probationary_statutory_max_days', 180);
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $driverDefault = (float) CompanySetting::getValue('driver_default_baseline_salary', 28000.00);
        $staffDefault = (float) CompanySetting::getValue('staff_default_baseline_salary', 25000.00);

        $hireDate = $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date) : now()->subMonths(5);
        $daysRendered = (int) abs(now()->diffInDays($hireDate));
        $daysRemaining = max(0, $maxProbationDays - $daysRendered);
        $milestoneReached = $daysRendered >= ($maxProbationDays - 30); // At or beyond 5th month evaluation

        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
        $currentSalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * $workingDays : ($isDriver ? $driverDefault : $staffDefault)));

        $rating = $employee->performance_rating ?? 'Satisfactory';
        $score = $this->resolveScoreFromRating($rating);

        $grade = SalaryGrade::where('position_name', $employee->position)->first()
            ?? SalaryGrade::where('min_salary', '<=', $currentSalary)->where('max_salary', '>=', $currentSalary)->first()
            ?? SalaryGrade::first();

        // Step 2 alignment or default regularization increment
        $step2 = SalaryStep::where('salary_grade_id', $grade->id)->where('step_number', 2)->first();
        $honorsRaisePct = (float) CompanySetting::getValue('probation_honors_raise_pct', 10.0);
        $standardRaisePct = (float) CompanySetting::getValue('probation_standard_raise_pct', 7.5);
        $recommendedBumpPct = $score >= 4.5 ? $honorsRaisePct : ($score >= 3.0 ? $standardRaisePct : 0.0);

        $recommendedSalary = $step2 ? (float) $step2->step_salary : round($currentSalary * (1 + ($recommendedBumpPct / 100)), 2);
        if ($recommendedSalary <= $currentSalary && $score >= 3.0) {
            $recommendedSalary = round($currentSalary * (1 + ($recommendedBumpPct / 100)), 2);
        }

        $isEligible = $score >= 3.0;
        $recommendation = $isEligible
            ? ($score >= 4.5 ? "Strongly Recommended: Regularize with Step 2 Placement + Honors Bump ({$honorsRaisePct}%)" : "Recommended: Regularize with Standard {$standardRaisePct}% Increment")
            : 'Not Recommended: Below 3.0 Performance Benchmark (Extend or Issue Separation Notice)';

        $defaultAllowance = (float) CompanySetting::getValue('standard_employee_allowance_monthly', 3500.00);
        $oldCtc = $this->counterOfferService->calculateTotalCostToCompany($currentSalary, $defaultAllowance);
        $newCtc = $this->counterOfferService->calculateTotalCostToCompany($recommendedSalary, $defaultAllowance);

        return [
            'employee_id' => $employee->id,
            'employee_name' => "{$employee->first_name} {$employee->last_name}",
            'employee_code' => $employee->employee_code,
            'position' => $employee->position,
            'department' => $employee->department?->name ?? 'Operations',
            'hire_date' => $hireDate->format('Y-m-d'),
            'days_rendered' => $daysRendered,
            'days_remaining_to_180' => $daysRemaining,
            'milestone_reached' => $milestoneReached,
            'performance_rating' => $rating,
            'performance_score' => $score,
            'is_eligible' => $isEligible,
            'recommendation' => $recommendation,
            'current_salary' => $currentSalary,
            'recommended_salary' => $recommendedSalary,
            'increment_amount' => round($recommendedSalary - $currentSalary, 2),
            'ctc_impact' => [
                'current_monthly_ctc' => $oldCtc['monthly_ctc'],
                'regularized_monthly_ctc' => $newCtc['monthly_ctc'],
                'incremental_annual_ctc' => round($newCtc['annual_ctc'] - $oldCtc['annual_ctc'], 2),
            ],
            'dole_compliance' => "DOLE Art. 296 180-Day Window: {$daysRendered}/180 days completed ({$daysRemaining} days remaining).",
        ];
    }

    /**
     * Perform Regularization of Probationary Employee
     */
    public function regularizeEmployee(Employee $employee, float $newSalary, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($employee, $newSalary, $notes) {
            $oldSalary = (float) $employee->monthly_rate;
            $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');

            $updateData = [
                'employment_status' => 'regular',
                'regularization_date' => now(),
                'monthly_rate' => $newSalary,
                'current_step' => max(2, (int) ($employee->current_step ?? 1)),
                'step_status' => 'normal',
            ];

            if ($isDriver) {
                $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
                $updateData['daily_rate'] = round($newSalary / $workingDays, 2);
            }

            $employee->update($updateData);

            // Compute CTC
            $defaultAllowance = (float) CompanySetting::getValue('standard_employee_allowance_monthly', 3500.00);
            $ctc = $this->counterOfferService->calculateTotalCostToCompany($newSalary, $defaultAllowance);

            // Record in CompensationAdjustment
            CompensationAdjustment::create([
                'employee_id' => $employee->id,
                'subject_type' => 'employee',
                'type' => 'probationary_conversion',
                'mode' => 'mode_a',
                'old_rate' => $oldSalary,
                'new_rate' => $newSalary,
                'monthly_ctc' => $ctc['monthly_ctc'],
                'annual_ctc' => $ctc['annual_ctc'],
                'thirteenth_month_liability' => $ctc['thirteenth_month_liability'],
                'employer_statutory_total' => $ctc['employer_statutory']['total'],
                'old_position' => $employee->position,
                'new_position' => $employee->position,
                'status' => 'approved',
                'budget_impact_status' => 'BUDGET_APPROVED',
                'reason' => $notes ?? 'Successful completion of 6-month probationary period and performance validation.',
                'effective_date' => now(),
            ]);

            PayrollAuditTrail::create([
                'action' => 'PROBATIONARY_REGULARIZATION_APPROVED',
                'model_type' => Employee::class,
                'model_id' => $employee->id,
                'user_name' => 'HR Manager',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['employment_status' => 'probationary', 'monthly_rate' => $oldSalary],
                'new_values' => ['employment_status' => 'regular', 'monthly_rate' => $newSalary, 'regularization_date' => now()->toDateString()],
            ]);

            return true;
        });
    }

    /**
     * Extend Probationary Period
     */
    public function extendProbation(Employee $employee, int $months = 1, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($employee, $months, $reason) {
            PayrollAuditTrail::create([
                'action' => 'PROBATION_PERIOD_EXTENDED',
                'model_type' => Employee::class,
                'model_id' => $employee->id,
                'user_name' => 'HR Manager',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['employment_status' => 'probationary'],
                'new_values' => ['employment_status' => 'probationary', 'extension_months' => $months, 'reason' => $reason ?? 'Performance evaluation extension'],
            ]);

            return true;
        });
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
}
