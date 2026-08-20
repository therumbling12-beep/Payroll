<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\CompanySetting;
use App\Models\GovernmentContributionTable;

class PagIbigContributionService
{
    /**
     * Compute Pag-IBIG / HDMF employee and employer shares based on HDMF Circular No. 460.
     * Supports voluntary employee contributions exceeding the standard cap (docs/no.md Line 117).
     *
     * @return array{employee_share: float, employer_share: float, total_contribution: float}
     */
    public function compute(
        float $monthlyBasicSalary,
        bool $isSemiMonthly = false,
        ?float $voluntaryContribution = null,
        bool $isWeekly = false
    ): array {
        $lowThreshold = (float) CompanySetting::getValue('pagibig_low_income_threshold', 1500.00);
        $lowEeRate = (float) CompanySetting::getValue('pagibig_low_income_ee_rate', 0.01);
        $stdEeRate = (float) CompanySetting::getValue('pagibig_standard_ee_rate', 0.02);
        $stdErRate = (float) CompanySetting::getValue('pagibig_standard_er_rate', 0.02);
        $monthlyCap = (float) CompanySetting::getValue('pagibig_standard_monthly_cap', 200.00);

        if ($monthlyBasicSalary <= $lowThreshold) {
            $eeRate = $lowEeRate;
            $erRate = $stdErRate;
            $eeShare = min($monthlyCap, round($monthlyBasicSalary * $eeRate, 2));
            $erShare = min($monthlyCap, round($monthlyBasicSalary * $erRate, 2));
        } else {
            $eeRate = $stdEeRate;
            $erRate = $stdErRate;
            $eeShare = min($monthlyCap, round($monthlyBasicSalary * $eeRate, 2));
            $erShare = min($monthlyCap, round($monthlyBasicSalary * $erRate, 2));
        }

        // Support voluntary employee contribution higher than standard cap (docs/no.md Line 117)
        if ($voluntaryContribution !== null && $voluntaryContribution > $eeShare) {
            $eeShare = round($voluntaryContribution, 2);
        }

        if ($isWeekly) {
            $weeksPerYear = (float) CompanySetting::getValue('payroll_standard_weeks_per_year', 52.0);
            $eeShare = round(($eeShare * 12) / $weeksPerYear, 2);
            $erShare = round(($erShare * 12) / $weeksPerYear, 2);
        } elseif ($isSemiMonthly) {
            $eeShare = round($eeShare / 2, 2);
            $erShare = round($erShare / 2, 2);
        }

        return [
            'employee_share' => $eeShare,
            'employer_share' => $erShare,
            'total_contribution' => round($eeShare + $erShare, 2),
        ];
    }
}
