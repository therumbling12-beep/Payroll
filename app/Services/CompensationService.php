<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\PerformanceBonus;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompensationService
{
    public function __construct(
        protected FinancialService $financialService
    ) {}

    /**
     * Calculate projected salary based on years of service and salary grade ranges / step tables.
     * (Section 2.12 Tenure Step Process)
     */
    public function calculateSalaryGrowth(Employee $employee, int $additionalYears): array
    {
        $currentSalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * 26 : 25000.00));
        $grade = SalaryGrade::with('steps')->where('position_name', $employee->position)->first();

        if (! $grade) {
            $posLower = strtolower($employee->position);
            $grade = SalaryGrade::with('steps')->get()->first(function ($sg) use ($posLower) {
                return str_contains($posLower, strtolower(explode(' ', $sg->position_name)[0]));
            });
        }

        $maxSalary = $grade ? (float) $grade->max_salary : ($currentSalary * 2);
        $currentTenure = (float) $employee->years_of_service;
        $targetTenure = $currentTenure + $additionalYears;

        $projectedSalary = $currentSalary;

        // 1. Check if SalaryStep table rules are defined for this grade
        if ($grade && $grade->steps->isNotEmpty()) {
            $steps = $grade->steps->sortBy('years_required');
            $applicableStep = $steps->filter(fn (SalaryStep $step) => $targetTenure >= (float) $step->years_required)->last()
                ?? $steps->first();

            if ($applicableStep) {
                $baseSalary = (float) $grade->min_salary;
                $pct = (float) $applicableStep->increment_percentage;
                $stepAmount = $applicableStep->base_amount
                    ? (float) $applicableStep->base_amount
                    : ($baseSalary + ($baseSalary * ($pct / 100)));

                $projectedSalary = max($currentSalary, $stepAmount);
            }
        } else {
            // 2. Fallback to annual growth rate percentage formula
            $growthRate = $grade ? ($grade->annual_growth_rate / 100) : 0.05;
            for ($i = 0; $i < $additionalYears; $i++) {
                $projectedSalary += ($projectedSalary * $growthRate);
            }
        }

        $projectedSalary = min($projectedSalary, $maxSalary);

        return [
            'employee_id' => $employee->id,
            'employee_name' => "{$employee->first_name} {$employee->last_name}",
            'position' => $employee->position,
            'current_salary' => $currentSalary,
            'current_tenure_years' => $currentTenure,
            'years_added' => $additionalYears,
            'target_tenure_years' => $targetTenure,
            'projected_salary' => round($projectedSalary, 2),
            'max_grade_cap' => $maxSalary,
        ];
    }

    /**
     * Automated Credential-Based Counter Offer & Offer Package Builder.
     * (Section 2.3 Credentials & 2.6 Applicants & 2.13 Counter Offer & Prof Notes #1, #4)
     */
    public function computeCounterOffer(
        string $position,
        int $yearsExperience = 0,
        int $certificationsCount = 0,
        float $competitorOffer = 0.0,
        float $currentSalary = 0.0,
        string $educationLevel = 'College Graduate',
        string $performanceRating = 'Satisfactory'
    ): array {
        $grade = SalaryGrade::where('position_name', $position)->first();
        if (! $grade) {
            $posLower = strtolower($position);
            $grade = SalaryGrade::all()->first(function ($sg) use ($posLower) {
                return str_contains($posLower, strtolower(explode(' ', $sg->position_name)[0]));
            });
        }

        $baseSalary = $grade ? (float) $grade->min_salary : 25000.00;
        $maxSalary = $grade ? (float) $grade->max_salary : 60000.00;
        $midpoint = ($baseSalary + $maxSalary) / 2;

        $expMult = (float) CompanySetting::getValue('counter_offer_exp_multiplier', 2000.00);
        $certMult = (float) CompanySetting::getValue('counter_offer_cert_multiplier', 2500.00);

        // Education Scoring (Section 2.3)
        $educationBonus = match (strtolower(trim($educationLevel))) {
            'masteral+' => 4000.00,
            'college graduate' => 2000.00,
            'vocational' => 1000.00,
            default => 0.00,
        };

        // Performance Multiplier Scoring (Section 2.10 & Prof Note #4)
        $perfMult = match (strtolower(trim($performanceRating))) {
            'outstanding' => 1.15,
            'very satisfactory' => 1.08,
            'satisfactory' => 1.00,
            'needs improvement' => 0.90,
            default => 1.00,
        };

        // Formula: (Base + ExpBonus + CertBonus + EduBonus) * PerfMultiplier
        $experienceBonus = $yearsExperience * $expMult;
        $certBonus = $certificationsCount * $certMult;

        $calculatedSalary = ($baseSalary + $experienceBonus + $certBonus + $educationBonus) * $perfMult;

        // If competitor offer is provided and higher, attempt to match or exceed within grade cap
        if ($competitorOffer > 0) {
            $offeredSalary = min($maxSalary, max($calculatedSalary, $competitorOffer * 1.05));
        } else {
            $offeredSalary = min($maxSalary, $calculatedSalary);
        }

        // Peer equity analysis against current active employees in the same position
        $peerSalaries = Employee::where('position', $position)
            ->whereNotNull('monthly_rate')
            ->where('monthly_rate', '>', 0)
            ->pluck('monthly_rate');

        $peerAverage = $peerSalaries->isNotEmpty() ? (float) $peerSalaries->avg() : $midpoint;
        $peerEquityGap = $offeredSalary - $peerAverage;
        $peerEquityStatus = abs($peerEquityGap) > ($peerAverage * 0.15) ? 'Review Required (High Deviation)' : 'Within Peer Norm';

        // Calculate retention risk based on competitor offer gap vs current salary
        $baseline = $currentSalary > 0 ? $currentSalary : $baseSalary;
        $gapPct = $competitorOffer > 0 ? (($competitorOffer - $baseline) / max(1, $baseline)) * 100 : 0;

        $retentionRisk = match (true) {
            $gapPct >= 20 => 'high',
            $gapPct >= 8 => 'medium',
            default => 'low',
        };

        $recommendedAction = match ($retentionRisk) {
            'high' => 'High Poaching Risk: Match competitor rate + add ₱3,000–₱5,000 signing/retention bonus to close.',
            'medium' => 'Moderate Risk: Offer competitive midpoint alignment + highlight stability and benefits.',
            default => 'Standard Package: Candidate/Employee compensation aligns comfortably with internal pay band.',
        };

        // Standard allowances package (Section 2.6 Offer Package Builder)
        $transpoAllowance = 2000.00;
        $mealAllowance = 1500.00;
        $signingBonus = $retentionRisk === 'high' ? 5000.00 : 0.00;
        $totalPackage = $offeredSalary + $transpoAllowance + $mealAllowance + $signingBonus;

        // Check budget availability via FinancialService
        $budgetCheck = $this->financialService->checkBudgetAvailability($offeredSalary, 'Human Resources');

        return [
            'position' => $position,
            'education_level' => $educationLevel,
            'performance_rating' => $performanceRating,
            'years_experience' => $yearsExperience,
            'certifications_count' => $certificationsCount,
            'base_salary' => $baseSalary,
            'midpoint_salary' => round($midpoint, 2),
            'max_allowed' => $maxSalary,
            'computed_counter_offer' => round($offeredSalary, 2),
            'transportation_allowance' => $transpoAllowance,
            'meal_allowance' => $mealAllowance,
            'signing_bonus' => $signingBonus,
            'total_package' => round($totalPackage, 2),
            'peer_average_salary' => round($peerAverage, 2),
            'peer_equity_gap' => round($peerEquityGap, 2),
            'peer_equity_status' => $peerEquityStatus,
            'competitor_offer' => round($competitorOffer, 2),
            'gap_percentage' => round($gapPct, 1),
            'retention_risk' => $retentionRisk,
            'recommended_action' => $recommendedAction,
            'financial_budget_check' => $budgetCheck,
        ];
    }

    /**
     * Build Complete Salary Offer Package for an Applicant.
     * (Section 2.6 Compensation for Applicants & Team 1 Integration)
     */
    public function buildOfferPackage(array $applicantData): array
    {
        $position = $applicantData['position'] ?? 'Operations Dispatcher';
        $expectedSalary = (float) ($applicantData['expected_salary'] ?? 0.0);
        $yearsExp = (int) ($applicantData['years_experience'] ?? 0);
        $certsCount = (int) ($applicantData['certifications_count'] ?? 0);
        $education = $applicantData['education_level'] ?? 'College Graduate';

        $computed = $this->computeCounterOffer(
            $position,
            $yearsExp,
            $certsCount,
            $expectedSalary,
            0.0,
            $education
        );

        $offeredBase = $computed['computed_counter_offer'];
        $transpo = 2000.00;
        $meal = 1500.00;
        $signingBonus = (float) ($applicantData['signing_bonus'] ?? 0.0);
        $totalOffered = $offeredBase + $transpo + $meal + $signingBonus;

        $basicDiff = $offeredBase - $expectedSalary;
        $totalDiff = $totalOffered - ($expectedSalary + $transpo + $meal);

        return [
            'applicant_name' => $applicantData['applicant_name'] ?? 'Candidate',
            'position' => $position,
            'expected_salary' => $expectedSalary,
            'offered_basic' => $offeredBase,
            'transportation_allowance' => $transpo,
            'meal_allowance' => $meal,
            'hmo_coverage' => $applicantData['hmo_tier'] ?? 'Individual',
            'signing_bonus' => $signingBonus,
            'total_package' => round($totalOffered, 2),
            'exceeds_band_max' => $expectedSalary > $computed['max_allowed'],
            'salary_band' => [
                'min' => $computed['base_salary'],
                'mid' => $computed['midpoint_salary'],
                'max' => $computed['max_allowed'],
            ],
            'comparison' => [
                'basic_difference' => round($basicDiff, 2),
                'basic_status' => $basicDiff >= 0 ? 'higher' : 'lower',
                'total_difference' => round($totalDiff, 2),
                'total_status' => $totalDiff >= 0 ? 'higher' : 'lower',
            ],
            'financial_budget_check' => $computed['financial_budget_check'],
        ];
    }

    /**
     * Update a Salary Band with Effectivity Tracking.
     * (Section 2.2 Salary Band Management & Prof Note #5)
     */
    public function updateSalaryBand(
        SalaryGrade $grade,
        float $minSalary,
        float $maxSalary,
        ?float $growthRate = null,
        ?string $effectivityDate = null
    ): array {
        return DB::transaction(function () use ($grade, $minSalary, $maxSalary, $growthRate, $effectivityDate) {
            $oldValues = $grade->toArray();

            $grade->update([
                'min_salary' => $minSalary,
                'max_salary' => $maxSalary,
                'annual_growth_rate' => $growthRate ?? $grade->annual_growth_rate,
                'effectivity_date' => $effectivityDate ? date('Y-m-d', strtotime($effectivityDate)) : now(),
            ]);

            // Auto update step 1 to match new min salary
            $step1 = $grade->steps()->where('step_number', 1)->first();
            if ($step1) {
                $step1->update(['base_amount' => $minSalary]);
            }

            // Log to Audit Trail
            PayrollAuditTrail::create([
                'user_name' => 'HR Compensation Head',
                'action' => 'SALARY_BAND_UPDATE',
                'model_type' => 'SalaryGrade',
                'model_id' => $grade->id,
                'old_values' => $oldValues,
                'new_values' => $grade->fresh()->toArray(),
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);

            return [
                'success' => true,
                'grade' => $grade->fresh(),
            ];
        });
    }

    /**
     * Bulk Adjust All Salary Bands for Annual Market Adjustment.
     * (Section 2.2 Salary Band Management)
     */
    public function bulkAdjustBands(float $percentage): array
    {
        return DB::transaction(function () use ($percentage) {
            $multiplier = 1 + ($percentage / 100);
            $grades = SalaryGrade::all();
            $updatedCount = 0;

            foreach ($grades as $grade) {
                $oldValues = $grade->toArray();
                $newMin = round($grade->min_salary * $multiplier, 2);
                $newMax = round($grade->max_salary * $multiplier, 2);

                $grade->update([
                    'min_salary' => $newMin,
                    'max_salary' => $newMax,
                    'effectivity_date' => now(),
                ]);

                // Update steps proportionally
                foreach ($grade->steps as $step) {
                    if ($step->base_amount) {
                        $step->update(['base_amount' => round($step->base_amount * $multiplier, 2)]);
                    }
                }

                PayrollAuditTrail::create([
                    'user_name' => 'HR Compensation Head',
                    'action' => 'SALARY_BAND_BULK_ADJUST',
                    'model_type' => 'SalaryGrade',
                    'model_id' => $grade->id,
                    'old_values' => $oldValues,
                    'new_values' => $grade->fresh()->toArray(),
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                ]);

                $updatedCount++;
            }

            return [
                'success' => true,
                'updated_grades_count' => $updatedCount,
                'percentage' => $percentage,
            ];
        });
    }

    /**
     * Retrieve Probationary Employees & Milestone Evaluation Status.
     * (Section 2.8 Probationary to Regular Conversion)
     */
    public function getProbationaryOverview(): array
    {
        $probationaryEmployees = Employee::with('department')
            ->where('employment_status', 'probationary')
            ->orWhere(function ($q) {
                $q->whereNull('employment_status')
                    ->where('hire_date', '>=', now()->subMonths(6));
            })
            ->get();

        $critical7Days = [];
        $due30Days = [];
        $review60Days = [];
        $onTrack = [];

        foreach ($probationaryEmployees as $emp) {
            $hireDate = $emp->hire_date ?? now()->subMonths(3);
            $targetRegularization = $hireDate->copy()->addMonths(6);
            $daysLeft = (int) now()->diffInDays($targetRegularization, false);

            $currentSalary = (float) ($emp->monthly_rate ?: ($emp->daily_rate ? $emp->daily_rate * 26 : 20000.00));
            // Standard recommended regularization raise: +10% or midpoint of grade
            $grade = SalaryGrade::where('position_name', $emp->position)->first();
            $midpoint = $grade ? (($grade->min_salary + $grade->max_salary) / 2) : ($currentSalary * 1.10);
            $suggestedRegularRate = max($currentSalary * 1.08, $midpoint);

            $item = [
                'employee' => $emp,
                'hire_date' => $hireDate->format('M j, Y'),
                'target_date' => $targetRegularization->format('M j, Y'),
                'days_remaining' => $daysLeft,
                'current_salary' => $currentSalary,
                'suggested_regular_salary' => round($suggestedRegularRate, 2),
            ];

            if ($daysLeft <= 7) {
                $critical7Days[] = $item;
            } elseif ($daysLeft <= 30) {
                $due30Days[] = $item;
            } elseif ($daysLeft <= 60) {
                $review60Days[] = $item;
            } else {
                $onTrack[] = $item;
            }
        }

        $all = array_merge($critical7Days, $due30Days, $review60Days, $onTrack);

        return [
            'total_probationary' => $probationaryEmployees->count(),
            'critical_7_days' => $critical7Days,
            'due_30_days' => $due30Days,
            'notice_60_days' => $review60Days,
            'review_60_days' => $review60Days,
            'on_track' => $onTrack,
            'upcoming' => $onTrack,
            'employees' => $all,
        ];
    }

    /**
     * Execute Regularization of an Employee.
     * (Section 2.8 Regularization Workflow)
     */
    public function regularizeEmployee(Employee $employee, float $newRate, ?string $reason = null): array
    {
        return DB::transaction(function () use ($employee, $newRate, $reason) {
            $oldRate = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * 26 : 0.00));

            $employee->employment_status = 'regular';
            $employee->regularization_date = now();

            $isDriver = str_contains($employee->position, 'Driver');
            if ($isDriver) {
                $employee->daily_rate = round($newRate / 26, 2);
                $employee->monthly_rate = $newRate;
            } else {
                $employee->monthly_rate = $newRate;
            }
            $employee->save();

            // Create logged compensation adjustment record
            $adjustment = CompensationAdjustment::create([
                'employee_id' => $employee->id,
                'subject_type' => 'employee',
                'type' => 'salary_config',
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'old_position' => $employee->position,
                'new_position' => $employee->position,
                'reason' => $reason ?? 'Official Regularization from Probationary Status: Standard Band Rate Increase',
                'status' => 'approved',
                'effective_date' => now(),
            ]);

            return [
                'success' => true,
                'employee' => $employee->fresh(),
                'adjustment' => $adjustment,
            ];
        });
    }

    /**
     * Get Tenure Step Grid and Eligible Employees.
     * (Section 2.12 Tenure Step Process)
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
            $baseSalary = (float) $grade->min_salary;

            $nextSalary = $currentSalary;
            if ($nextStep) {
                $pct = (float) $nextStep->increment_percentage;
                $nextSalary = $nextStep->base_amount
                    ? (float) $nextStep->base_amount
                    : ($baseSalary + ($baseSalary * ($pct / 100)));
            }

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

    /**
     * Apply Tenure Step Increment to an Employee.
     * (Section 2.12.4 Step Approval Workflow)
     */
    public function applyStepIncrement(Employee $employee, int $targetStep, float $newRate, ?string $reason = null): array
    {
        return DB::transaction(function () use ($employee, $targetStep, $newRate, $reason) {
            $oldRate = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * 26 : 0.0));

            $employee->current_step = $targetStep;
            $employee->step_status = 'normal';
            $employee->step_hold_reason = null;

            $isDriver = str_contains($employee->position, 'Driver');
            if ($isDriver) {
                $employee->daily_rate = round($newRate / 26, 2);
                $employee->monthly_rate = $newRate;
            } else {
                $employee->monthly_rate = $newRate;
            }
            $employee->save();

            $adjustment = CompensationAdjustment::create([
                'employee_id' => $employee->id,
                'subject_type' => 'employee',
                'type' => 'salary_config',
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
                'old_position' => $employee->position,
                'new_position' => $employee->position,
                'reason' => $reason ?? "Applied Tenure Step {$targetStep} Increment based on completed years of service.",
                'status' => 'approved',
                'effective_date' => now(),
            ]);

            return [
                'success' => true,
                'employee' => $employee->fresh(),
                'adjustment' => $adjustment,
            ];
        });
    }

    /**
     * Put a Step Increment on Hold.
     * (Section 2.12.5 Step Hold Conditions)
     */
    public function holdStepIncrement(Employee $employee, string $reason): array
    {
        $employee->step_status = 'on_hold';
        $employee->step_hold_reason = $reason;
        $employee->save();

        PayrollAuditTrail::create([
            'user_name' => 'HR Compensation Head',
            'action' => 'STEP_INCREMENT_HOLD',
            'model_type' => 'Employee',
            'model_id' => $employee->id,
            'new_values' => [
                'employee_code' => $employee->employee_code,
                'reason' => $reason,
                'status' => 'on_hold',
            ],
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);

        return [
            'success' => true,
            'employee' => $employee->fresh(),
        ];
    }

    /**
     * Distribute Bonus Pool to Employees with Performance Multiplier.
     * (Section 2.11 Bonus Allocation & Prof Note #4)
     */
    public function allocateBonuses(array $allocations, string $bonusType, float $poolAmount, ?int $departmentId = null): array
    {
        return DB::transaction(function () use ($allocations, $bonusType, $poolAmount, $departmentId) {
            $createdCount = 0;
            $totalAllocated = 0.0;
            $cutoff = now()->format('Y-m');

            foreach ($allocations as $item) {
                $employeeId = (int) ($item['employee_id'] ?? 0);
                $amount = (float) ($item['bonus_amount'] ?? 0.0);
                $reason = $item['reason'] ?? "{$bonusType} Allocation ({$cutoff})";

                if ($employeeId > 0 && $amount > 0) {
                    PerformanceBonus::create([
                        'employee_id' => $employeeId,
                        'cutoff_period' => $cutoff,
                        'bonus_amount' => $amount,
                        'reason' => $reason,
                    ]);

                    $totalAllocated += $amount;
                    $createdCount++;
                }
            }

            // Log to Audit Trail
            PayrollAuditTrail::create([
                'user_name' => 'HR Compensation Head',
                'action' => 'BONUS_POOL_ALLOCATION',
                'model_type' => 'PerformanceBonus',
                'model_id' => $createdCount,
                'new_values' => [
                    'bonus_type' => $bonusType,
                    'pool_amount' => $poolAmount,
                    'total_allocated' => $totalAllocated,
                    'employees_count' => $createdCount,
                    'department_id' => $departmentId,
                ],
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);

            return [
                'success' => true,
                'created_count' => $createdCount,
                'total_allocated' => round($totalAllocated, 2),
                'pool_amount' => round($poolAmount, 2),
                'remaining_pool' => round(max(0, $poolAmount - $totalAllocated), 2),
            ];
        });
    }
}
