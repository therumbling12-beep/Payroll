<?php

declare(strict_types=1);

namespace App\Services\Payroll;

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
        $config = GovernmentContributionTable::where('table_type', 'PHILHEALTH')->first();

        $floor = $config ? (float) $config->bracket_from : 10000.00;
        $ceiling = $config ? (float) $config->bracket_to : 100000.00;
        $rate = $config ? (float) $config->employee_rate : 0.0250;

        if ($monthlyBasicSalary <= $floor) {
            $eeShare = 250.00;
            $erShare = 250.00;
            $totalPremium = 500.00;
        } elseif ($monthlyBasicSalary >= $ceiling) {
            $eeShare = 2500.00;
            $erShare = 2500.00;
            $totalPremium = 5000.00;
        } else {
            $totalPremium = round($monthlyBasicSalary * 0.05, 2);
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
