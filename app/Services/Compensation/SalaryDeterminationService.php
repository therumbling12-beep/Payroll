<?php

declare(strict_types=1);

namespace App\Services\Compensation;

use App\Models\CompanySetting;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryGrade;
use Illuminate\Support\Facades\DB;

class SalaryDeterminationService
{
    /**
     * Compute 5-Factor or 6-Factor Weighted Candidate Scoring Equation (docs/no.md Lines 28–34 & known.md §6.3)
     */
    public function computeSalaryScore(
        int $arg1 = 3,
        int $arg2 = 2,
        int $arg3 = 3,
        int $arg4 = 3,
        int $arg5 = 3,
        ?int $arg6 = null
    ): float {
        if ($arg6 === null) {
            // 5-Factor Mode (known.md §6.3: Education, Experience, Skills, Market, Equity)
            $eduScore = max(1, min(6, $arg1)) * 0.25;
            $expScore = max(1, min(6, $arg2)) * 0.35;
            $skillScore = max(1, min(6, $arg3)) * 0.20;
            $marketScore = max(1, min(6, $arg4)) * 0.10;
            $equityScore = max(1, min(6, $arg5)) * 0.10;

            return round($eduScore + $expScore + $skillScore + $marketScore + $equityScore, 2);
        }

        // 6-Factor Mode (docs/no.md Lines 28–34: Experience, Skills, Education, Certifications, Prev Salary, Interview)
        $expScore = max(1, min(6, $arg1)) * 0.25;
        $skillScore = max(1, min(6, $arg2)) * 0.20;
        $eduScore = max(1, min(6, $arg3)) * 0.15;
        $certScore = max(1, min(6, $arg4)) * 0.15;
        $prevSalaryScore = max(1, min(6, $arg5)) * 0.15;
        $interviewScore = max(1, min(6, $arg6)) * 0.10;

        return round($expScore + $skillScore + $eduScore + $certScore + $prevSalaryScore + $interviewScore, 2);
    }

    /**
     * Determine Band Placement and Recommended Starting Salary (docs/no.md §1 & known.md §6.3)
     *
     * @param array<string, int> $factors
     * @return array<string, mixed>
     */
    public function calculateRecommendedSalary(?SalaryGrade $grade, array $factors = []): array
    {
        $education = (int) ($factors['education'] ?? 3);
        $experience = (int) ($factors['experience'] ?? 2);
        $skills = (int) ($factors['skills'] ?? 3);
        $certifications = (int) ($factors['certifications'] ?? ($factors['market_benchmark'] ?? 3));
        $marketBenchmark = (int) ($factors['market_benchmark'] ?? ($factors['certifications'] ?? 3));
        $previousSalary = (int) ($factors['previous_salary'] ?? ($factors['internal_equity'] ?? 3));
        $internalEquity = (int) ($factors['internal_equity'] ?? ($factors['previous_salary'] ?? 3));
        $interviewPerformance = (int) ($factors['interview_performance'] ?? 3);

        $isExplicit6Factor = isset($factors['interview_performance']) || isset($factors['certifications']) || isset($factors['previous_salary']);

        if ($isExplicit6Factor) {
            $totalScore = $this->computeSalaryScore($experience, $skills, $education, $certifications, $previousSalary, $interviewPerformance);
        } else {
            $totalScore = $this->computeSalaryScore($education, $experience, $skills, $marketBenchmark, $internalEquity);
        }

        $localityMinWageDaily = (float) CompanySetting::getValue('minimum_wage_daily', 755.00);
        $localityMinWageMonthly = round($localityMinWageDaily * 26.0, 2); // 19,630.00

        $min = $grade ? max($localityMinWageMonthly, (float) $grade->min_salary) : $localityMinWageMonthly;
        $max = $grade ? (float) $grade->max_salary : round($min * 1.45, 2);

        if ($totalScore <= 2.00) {
            $placementLabel = 'Locality Baseline (Score 1–2)';
            $percentileDecimal = 0.00;
        } elseif ($totalScore <= 3.00) {
            $placementLabel = '25th Percentile (Mid-Low Score 2–3)';
            $percentileDecimal = 0.25;
        } elseif ($totalScore <= 4.00) {
            $placementLabel = '50th Percentile / Midpoint (Mid Score 3–4)';
            $percentileDecimal = 0.50;
        } elseif ($totalScore <= 5.00) {
            $placementLabel = '75th Percentile (Mid-High Score 4–5)';
            $percentileDecimal = 0.75;
        } else {
            $placementLabel = 'Maximum of Range (High Score 5–6)';
            $percentileDecimal = 1.00;
        }

        $recommendedSalary = max($localityMinWageMonthly, round($min + ($percentileDecimal * ($max - $min)), 2));
        $recommendedDailyRate = max($localityMinWageDaily, round($recommendedSalary / 26.0, 2));

        $formulaString = "Score {$totalScore}/6.00 -> {$placementLabel} -> PHP " . number_format($min, 2) . " + ({$percentileDecimal} x PHP " . number_format($max - $min, 2) . ") = PHP " . number_format($recommendedSalary, 2) . " (PHP " . number_format($recommendedDailyRate, 2) . "/day)";

        return [
            'grade_id' => $grade?->id,
            'grade_code' => $grade?->grade_code ?? 'FLEX-COMP',
            'job_level' => $grade?->job_level ?? 'Flexible Merit Level',
            'position_name' => $grade?->position_name ?? 'Candidate',
            'min_salary' => $min,
            'max_salary' => $max,
            'midpoint_salary' => round(($min + $max) / 2, 2),
            'spread_amount' => round($max - $min, 2),
            'factors' => [
                'education' => ['score' => $education, 'weight' => 0.15, 'weighted_points' => round($education * 0.15, 2), 'label' => 'Educational Attainment (no.md §1)'],
                'experience' => ['score' => $experience, 'weight' => 0.25, 'weighted_points' => round($experience * 0.25, 2), 'label' => 'Relevant Experience (no.md §1)'],
                'skills' => ['score' => $skills, 'weight' => 0.20, 'weighted_points' => round($skills * 0.20, 2), 'label' => 'Technical & Job Skills (no.md §1)'],
                'market_benchmark' => ['score' => $marketBenchmark, 'weight' => 0.10, 'weighted_points' => round($marketBenchmark * 0.10, 2), 'label' => 'Market Benchmark'],
                'internal_equity' => ['score' => $internalEquity, 'weight' => 0.10, 'weighted_points' => round($internalEquity * 0.10, 2), 'label' => 'Internal Equity'],
                'certifications' => ['score' => $certifications, 'weight' => 0.15, 'weighted_points' => round($certifications * 0.15, 2), 'label' => 'Professional Certifications (no.md §1)'],
                'previous_salary' => ['score' => $previousSalary, 'weight' => 0.15, 'weighted_points' => round($previousSalary * 0.15, 2), 'label' => 'Previous Salary Benchmark (no.md §1)'],
                'interview_performance' => ['score' => $interviewPerformance, 'weight' => 0.10, 'weighted_points' => round($interviewPerformance * 0.10, 2), 'label' => 'Interview Assessment (no.md §1)'],
            ],
            'total_score' => $totalScore,
            'placement_label' => $placementLabel,
            'percentile_decimal' => $percentileDecimal,
            'recommended_salary' => $recommendedSalary,
            'recommended_daily_rate' => $recommendedDailyRate,
            'formula' => $formulaString,
            'minimum_wage_guard' => [
                'statutory_floor' => $localityMinWageMonthly,
                'daily_floor' => $localityMinWageDaily,
                'is_compliant' => true,
                'status' => 'COMPLIANT',
            ],
        ];
    }

    /**
     * Update a Salary Band with Effectivity Tracking and Step 1 Base Sync.
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
     * Bulk Adjust All Salary Bands and Steps for Annual Market Alignment.
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
}
