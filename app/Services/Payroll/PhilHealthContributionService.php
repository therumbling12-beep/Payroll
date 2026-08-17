<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\CompanySetting;
use App\Models\GovernmentContributionTable;

class PhilHealthContributionService
{
    /**
     * Compute PhilHealth employee and employer shares based on Advisory PA2025-0002 (5% Rate, ₱10k Floor, ₱100k Ceiling).
     *
     * @return array{total_premium: float, employee_share: float, employer_share: float}
     */
    public function compute(float $monthlyBasicSalary, bool $isSemiMonthly = false): array
    {
        $defaultFloor = (float) CompanySetting::getValue('philhealth_salary_floor', 10000.00);
        $defaultCeiling = (float) CompanySetting::getValue('philhealth_salary_ceiling', 100000.00);
        $defaultEeRate = (float) CompanySetting::getValue('philhealth_employee_rate', 0.0250);

        $config = GovernmentContributionTable::where('table_type', 'PHILHEALTH')->first();

        $floor = $config ? (float) $config->bracket_from : $defaultFloor;
        $ceiling = $config ? (float) $config->bracket_to : $defaultCeiling;
        $rate = $config ? (float) $config->employee_rate : $defaultEeRate;

        if ($monthlyBasicSalary <= $floor) {
            $eeShare = round($floor * $rate, 2);
            $erShare = $eeShare;
            $totalPremium = round($eeShare + $erShare, 2);
        } elseif ($monthlyBasicSalary >= $ceiling) {
            $eeShare = round($ceiling * $rate, 2);
            $erShare = $eeShare;
            $totalPremium = round($eeShare + $erShare, 2);
        } else {
            $totalPremium = round($monthlyBasicSalary * ($rate * 2), 2);
            $eeShare = round($monthlyBasicSalary * $rate, 2);
            $erShare = $eeShare;
        }

        if ($isSemiMonthly) {
            $eeShare = round($eeShare / 2, 2);
            $erShare = round($erShare / 2, 2);
            $totalPremium = round($totalPremium / 2, 2);
        }

        return [
            'total_premium' => $totalPremium,
            'employee_share' => $eeShare,
            'employer_share' => $erShare,
        ];
    }
}
