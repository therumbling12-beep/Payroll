<?php

declare(strict_types=1);

namespace App\Services\Compensation;

use App\Models\Employee;
use App\Models\SalaryGrade;

class CounterOfferService
{
    public function __construct(
        protected SalaryDeterminationService $determinationService
    ) {}

    /**
     * Mode A: Automated Credential-Based Counter Offer Computation (known.md §6.6)
     *
     * Formula:
     * - Evaluates 5-factor weighted candidate score.
     * - Max Counteroffer = MIN(Pay Grade Maximum, Competing Offer x 1.10).
     * - Detects exception escalation if proposed exceeds grade maximum.
     *
     * @param array<string, int> $factors
     * @return array<string, mixed>
     */
    public function computeModeA(
        SalaryGrade $grade,
        float $competitorOffer = 0.0,
        array $factors = [],
        ?Employee $employee = null
    ): array {
        $determination = $this->determinationService->calculateRecommendedSalary($grade, $factors);

        $gradeMin = (float) $grade->min_salary;
        $gradeMax = (float) $grade->max_salary;

        $targetOfferCap = round($competitorOffer * 1.10, 2);
        $maxStatutoryCap = min($gradeMax, $targetOfferCap > 0 ? $targetOfferCap : $gradeMax);

        $recommendedBase = (float) $determination['recommended_salary'];

        if ($competitorOffer > 0) {
            $proposedBase = min($maxStatutoryCap, max($recommendedBase, $targetOfferCap));
        } else {
            $proposedBase = $recommendedBase;
        }

        $exceedsBandMax = ($competitorOffer * 1.10) > $gradeMax;

        $ctc = $this->calculateTotalCostToCompany($proposedBase, 0.00, 0.00);

        $position = $employee ? $employee->position : $grade->position_name;
        $equity = $this->evaluateInternalEquity($position, $proposedBase, $employee?->id);

        return [
            'mode' => 'mode_a',
            'grade_id' => $grade->id,
            'grade_code' => $grade->grade_code,
            'job_level' => $grade->job_level,
            'position_name' => $position,
            'competitor_offer' => $competitorOffer,
            'target_offer_cap' => $targetOfferCap,
            'max_counteroffer_cap' => $maxStatutoryCap,
            'proposed_base_salary' => $proposedBase,
            'exceeds_band_maximum' => $exceedsBandMax,
            'determination' => $determination,
            'ctc' => $ctc,
            'internal_equity' => $equity,
            'formula' => "Max Counteroffer = MIN(Grade Max PHP " . number_format($gradeMax, 2) . ", Competing Offer x 1.10 PHP " . number_format($targetOfferCap, 2) . ") = PHP " . number_format($maxStatutoryCap, 2),
        ];
    }

    /**
     * Mode B: Manual Line-Item Component Builder (known.md §6.6)
     *
     * @return array<string, mixed>
     */
    public function computeModeB(
        SalaryGrade $grade,
        float $basicSalary,
        float $transportAllowance = 0.0,
        float $mealAllowance = 0.0,
        float $commsAllowance = 0.0,
        float $signingBonus = 0.0,
        ?Employee $employee = null
    ): array {
        $totalAllowances = round($transportAllowance + $mealAllowance + $commsAllowance, 2);
        $ctc = $this->calculateTotalCostToCompany($basicSalary, $totalAllowances, $signingBonus);

        $position = $employee ? $employee->position : $grade->position_name;
        $equity = $this->evaluateInternalEquity($position, $basicSalary, $employee?->id);

        $gradeMax = (float) $grade->max_salary;
        $exceedsBandMax = $basicSalary > $gradeMax;

        return [
            'mode' => 'mode_b',
            'grade_id' => $grade->id,
            'grade_code' => $grade->grade_code,
            'job_level' => $grade->job_level,
            'position_name' => $position,
            'basic_salary' => $basicSalary,
            'allowances' => [
                'transport' => $transportAllowance,
                'meal' => $mealAllowance,
                'comms' => $commsAllowance,
                'total' => $totalAllowances,
            ],
            'signing_bonus' => $signingBonus,
            'exceeds_band_maximum' => $exceedsBandMax,
            'ctc' => $ctc,
            'internal_equity' => $equity,
        ];
    }

    /**
     * Calculate Complete Total Cost to Company (CTC) Financial Liability
     *
     * Formula:
     * - Monthly CTC = Basic Salary + Total Allowances + ER SSS + ER PhilHealth + ER Pag-IBIG + EC
     * - Annual CTC = (Monthly CTC x 12) + Projected 13th Month Accrual + Signing Bonus
     *
     * @return array<string, mixed>
     */
    public function calculateTotalCostToCompany(
        float $baseSalary,
        float $totalAllowances = 0.0,
        float $signingBonus = 0.0
    ): array {
        // 1. Employer SSS Contribution (2026 standard ~10.0% of MSC capped at 30,000 MSC)
        $msc = min(30000.00, max(5000.00, $baseSalary));
        $erSss = round($msc * 0.10, 2);

        // 2. Employees' Compensation (EC) Program
        $erEc = 30.00;

        // 3. Employer PhilHealth (2.5% share, capped at 100k salary = 2,500.00 max ER share)
        $philHealthBasis = min(100000.00, max(10000.00, $baseSalary));
        $erPhilHealth = round($philHealthBasis * 0.025, 2);

        // 4. Employer Pag-IBIG (PHP 200.00 max standard employer share)
        $erPagIbig = 200.00;

        $employerStatutoryTotal = round($erSss + $erEc + $erPhilHealth + $erPagIbig, 2);

        $monthlyCtc = round($baseSalary + $totalAllowances + $employerStatutoryTotal, 2);

        $thirteenthMonthLiability = round($baseSalary, 2);

        $annualCtc = round(($monthlyCtc * 12) + $thirteenthMonthLiability + $signingBonus, 2);

        return [
            'base_salary' => $baseSalary,
            'total_allowances' => $totalAllowances,
            'employer_statutory' => [
                'sss' => $erSss,
                'ec' => $erEc,
                'philhealth' => $erPhilHealth,
                'pagibig' => $erPagIbig,
                'total' => $employerStatutoryTotal,
            ],
            'monthly_ctc' => $monthlyCtc,
            'thirteenth_month_liability' => $thirteenthMonthLiability,
            'signing_bonus' => $signingBonus,
            'annual_ctc' => $annualCtc,
            'formula' => "Annual CTC = (Monthly CTC PHP " . number_format($monthlyCtc, 2) . " x 12) + 13th Month PHP " . number_format($thirteenthMonthLiability, 2) . " + Signing Bonus PHP " . number_format($signingBonus, 2) . " = PHP " . number_format($annualCtc, 2),
        ];
    }

    /**
     * Evaluate Internal Equity & Wage Distortion Guard (known.md §6.6, §6.9)
     *
     * @return array<string, mixed>
     */
    public function evaluateInternalEquity(
        string $position,
        float $proposedSalary,
        ?int $excludeEmployeeId = null
    ): array {
        $query = Employee::where('position', $position)
            ->where('employment_status', '!=', 'resigned');

        if ($excludeEmployeeId) {
            $query->where('id', '!=', $excludeEmployeeId);
        }

        $peers = $query->get();
        $peerCount = $peers->count();

        if ($peerCount === 0) {
            return [
                'status' => 'NORMAL',
                'peer_count' => 0,
                'peer_median_salary' => $proposedSalary,
                'peer_min_salary' => $proposedSalary,
                'peer_max_salary' => $proposedSalary,
                'variance_percentage' => 0.0,
                'message' => 'First incumbent in position. No wage distortion detected against existing peers.',
            ];
        }

        $salaries = $peers->map(function (Employee $e) {
            $isDriver = str_contains(strtolower($e->position ?? ''), 'driver');
            return (float) ($e->monthly_rate ?: ($e->daily_rate ? $e->daily_rate * 26 : ($isDriver ? 28000.00 : 25000.00)));
        })->sort()->values();

        $count = $salaries->count();
        $mid = intdiv($count, 2);
        $peerMedian = ($count % 2 === 0)
            ? round(($salaries[$mid - 1] + $salaries[$mid]) / 2, 2)
            : round($salaries[$mid], 2);

        $peerMin = $salaries->min();
        $peerMax = $salaries->max();

        $variancePct = $peerMedian > 0 ? round((($proposedSalary - $peerMedian) / $peerMedian) * 100, 1) : 0.0;

        if ($proposedSalary > ($peerMedian * 1.15)) {
            $status = 'WAGE_DISTORTION_WARNING';
            $message = "Proposed salary of PHP " . number_format($proposedSalary, 2) . " exceeds peer median (PHP " . number_format($peerMedian, 2) . ") by {$variancePct}%, which may cause internal wage distortion.";
        } else {
            $status = 'NORMAL';
            $message = "Proposed offer is balanced with internal peer equity (Peer Median: PHP " . number_format($peerMedian, 2) . ").";
        }

        return [
            'status' => $status,
            'peer_count' => $peerCount,
            'peer_median_salary' => $peerMedian,
            'peer_min_salary' => $peerMin,
            'peer_max_salary' => $peerMax,
            'variance_percentage' => $variancePct,
            'message' => $message,
        ];
    }
}
