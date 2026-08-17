<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use Illuminate\Support\Facades\DB;

class CompensationService
{
    public function __construct(
        protected FinancialService $financialService
    ) {}

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
        $transpoAllowance = (float) CompanySetting::getValue('standard_transpo_allowance', 2000.00);
        $mealAllowance = (float) CompanySetting::getValue('standard_meal_allowance', 1500.00);
        $highRiskBonus = (float) CompanySetting::getValue('high_retention_risk_signing_bonus', 5000.00);
        $signingBonus = $retentionRisk === 'high' ? $highRiskBonus : 0.00;
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
}

