<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\CompanySetting;
use App\Models\GovernmentContributionTable;

class SssContributionService
{
    /**
     * Compute SSS employee, employer, and EC contributions based on 2025-2026 SSS Circular 2024-006.
     *
     * @return array{msc: float, employee_share: float, employer_share: float, ec_contribution: float, total_contribution: float}
     */
    public function compute(float $monthlyBasicSalary, bool $isSemiMonthly = false, bool $isWeekly = false): array
    {
        $bracket = GovernmentContributionTable::where('table_type', 'SSS')
            ->where('bracket_from', '<=', $monthlyBasicSalary)
            ->where(function ($query) use ($monthlyBasicSalary) {
                $query->where('bracket_to', '>=', $monthlyBasicSalary)
                    ->orWhereNull('bracket_to');
            })
            ->first();

        if ($bracket) {
            $msc = (float) $bracket->monthly_salary_credit;
            $eeShare = (float) $bracket->employee_fixed_amount;
            $erShare = (float) $bracket->employer_fixed_amount;
            $ec = (float) $bracket->ec_contribution;
        } else {
            // Heuristic fallback if table is not seeded
            $mscCeiling = (float) CompanySetting::getValue('sss_msc_ceiling', 35000.00);
            $mscFloor = (float) CompanySetting::getValue('sss_msc_floor', 5000.00);
            $eeRate = (float) CompanySetting::getValue('sss_ee_share_rate', 0.05);
            $erRate = (float) CompanySetting::getValue('sss_er_share_rate', 0.10);
            $ecThreshold = (float) CompanySetting::getValue('sss_ec_threshold', 15000.00);
            $ecHigh = (float) CompanySetting::getValue('sss_ec_high_amount', 30.00);
            $ecLow = (float) CompanySetting::getValue('sss_ec_low_amount', 10.00);

            $msc = min($mscCeiling, max($mscFloor, round($monthlyBasicSalary / 500) * 500));
            $eeShare = round($msc * $eeRate, 2);
            $erShare = round($msc * $erRate, 2);
            $ec = ($msc >= $ecThreshold) ? $ecHigh : $ecLow;
        }

        if ($isWeekly) {
            $weeksPerYear = (float) CompanySetting::getValue('payroll_standard_weeks_per_year', 52.0);
            $eeShare = round(($eeShare * 12) / $weeksPerYear, 2);
            $erShare = round(($erShare * 12) / $weeksPerYear, 2);
            $ec = round(($ec * 12) / $weeksPerYear, 2);
        } elseif ($isSemiMonthly) {
            $eeShare = round($eeShare / 2, 2);
            $erShare = round($erShare / 2, 2);
            $ec = round($ec / 2, 2);
        }

        return [
            'msc' => $msc,
            'employee_share' => $eeShare,
            'employer_share' => $erShare,
            'ec_contribution' => $ec,
            'total_contribution' => round($eeShare + $erShare + $ec, 2),
        ];
    }
}
