<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\GovernmentContributionTable;

class PagIbigContributionService
{
    /**
     * Compute Pag-IBIG / HDMF employee and employer shares based on HDMF Circular No. 460.
     *
     * @return array{employee_share: float, employer_share: float, total_contribution: float}
     */
    public function compute(float $monthlyBasicSalary, bool $isSemiMonthly = false): array
    {
        if ($monthlyBasicSalary <= 1500.00) {
            $eeRate = 0.01;
            $erRate = 0.02;
            $eeShare = min(200.00, round($monthlyBasicSalary * $eeRate, 2));
            $erShare = min(200.00, round($monthlyBasicSalary * $erRate, 2));
        } else {
            $eeRate = 0.02;
            $erRate = 0.02;
            $eeShare = min(200.00, round($monthlyBasicSalary * $eeRate, 2));
            $erShare = min(200.00, round($monthlyBasicSalary * $erRate, 2));
        }

        if ($isSemiMonthly) {
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
