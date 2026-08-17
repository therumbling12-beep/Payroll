<?php

declare(strict_types=1);

namespace App\Services\Compensation;

use App\Models\SalaryGrade;

class SalaryDeterminationService
{
    /**
     * Compute 5-Factor Weighted Candidate Scoring Equation (known.md §6.3)
     *
     * Weights:
     * - Education: 0.25 (1 to 6)
     * - Experience: 0.35 (1 to 6)
     * - Relevant Skills: 0.20 (1 to 6)
     * - Market Benchmark: 0.10 (1 to 6)
     * - Internal Equity: 0.10 (1 to 6)
     *
     * Total Score Scale: 1.00 to 6.00
     */
    public function computeSalaryScore(
        int $education = 3,
        int $experience = 2,
        int $skills = 3,
        int $marketBenchmark = 3,
        int $internalEquity = 3
    ): float {
        $eduScore = max(1, min(6, $education)) * 0.25;
        $expScore = max(1, min(6, $experience)) * 0.35;
        $skillScore = max(1, min(6, $skills)) * 0.20;
        $marketScore = max(1, min(6, $marketBenchmark)) * 0.10;
        $equityScore = max(1, min(6, $internalEquity)) * 0.10;

        return round($eduScore + $expScore + $skillScore + $marketScore + $equityScore, 2);
    }

    /**
     * Determine Band Placement and Recommended Starting Salary (known.md §6.3)
     *
     * Band Placement Mapping:
     * - Low (1.00 – 2.00): Minimum of Band (0% spread)
     * - Mid-Low (2.01 – 3.00): 25th Percentile (0.25 spread)
     * - Mid (3.01 – 4.00): 50th Percentile / Midpoint (0.50 spread)
     * - Mid-High (4.01 – 5.00): 75th Percentile (0.75 spread)
     * - High (5.01 – 6.00): Maximum of Band (100% spread)
     *
     * @param array<string, int> $factors
     * @return array<string, mixed>
     */
    public function calculateRecommendedSalary(SalaryGrade $grade, array $factors = []): array
    {
        $education = (int) ($factors['education'] ?? 3);
        $experience = (int) ($factors['experience'] ?? 2);
        $skills = (int) ($factors['skills'] ?? 3);
        $marketBenchmark = (int) ($factors['market_benchmark'] ?? 3);
        $internalEquity = (int) ($factors['internal_equity'] ?? 3);

        $totalScore = $this->computeSalaryScore($education, $experience, $skills, $marketBenchmark, $internalEquity);

        if ($totalScore <= 2.00) {
            $placementLabel = 'Minimum of Band (Low Score 1–2)';
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
            $placementLabel = 'Maximum of Band (High Score 5–6)';
            $percentileDecimal = 1.00;
        }

        $min = (float) $grade->min_salary;
        $max = (float) $grade->max_salary;
        $recommendedSalary = round($min + ($percentileDecimal * ($max - $min)), 2);

        // DOLE NCR-27 minimum wage compliance check (PHP 755/day = ~19,630/mo)
        $ncr27Floor = 19630.00;
        $isMinWageCompliant = $recommendedSalary >= $ncr27Floor;

        $formulaString = "Score {$totalScore}/6.00 -> {$placementLabel} -> PHP " . number_format($min, 2) . " + ({$percentileDecimal} x PHP " . number_format($max - $min, 2) . ") = PHP " . number_format($recommendedSalary, 2);

        return [
            'grade_id' => $grade->id,
            'grade_code' => $grade->grade_code,
            'job_level' => $grade->job_level,
            'position_name' => $grade->position_name,
            'min_salary' => $min,
            'max_salary' => $max,
            'midpoint_salary' => round(($min + $max) / 2, 2),
            'spread_amount' => round($max - $min, 2),
            'factors' => [
                'education' => ['score' => $education, 'weight' => 0.25, 'weighted_points' => round($education * 0.25, 2)],
                'experience' => ['score' => $experience, 'weight' => 0.35, 'weighted_points' => round($experience * 0.35, 2)],
                'skills' => ['score' => $skills, 'weight' => 0.20, 'weighted_points' => round($skills * 0.20, 2)],
                'market_benchmark' => ['score' => $marketBenchmark, 'weight' => 0.10, 'weighted_points' => round($marketBenchmark * 0.10, 2)],
                'internal_equity' => ['score' => $internalEquity, 'weight' => 0.10, 'weighted_points' => round($internalEquity * 0.10, 2)],
            ],
            'total_score' => $totalScore,
            'placement_label' => $placementLabel,
            'percentile_decimal' => $percentileDecimal,
            'recommended_salary' => $recommendedSalary,
            'formula' => $formulaString,
            'minimum_wage_guard' => [
                'statutory_floor' => $ncr27Floor,
                'is_compliant' => $isMinWageCompliant,
                'status' => $isMinWageCompliant ? 'COMPLIANT' : 'BELOW_MINIMUM_WAGE',
            ],
        ];
    }
}
