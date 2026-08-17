<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\GovernmentContributionTable;

class SssContributionService
{
    /**
     * Compute SSS employee, employer, and EC contributions based on 2025-2026 SSS Circular 2024-006.
     *
     * @return array{msc: float, employee_share: float, employer_share: float, ec_contribution: float, total_contribution: float}
     */
    public function compute(float $monthlyBasicSalary, bool $isSemiMonthly = false): array
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
            $msc = min(35000.00, max(5000.00, round($monthlyBasicSalary / 500) * 500));
            $eeShare = round($msc * 0.05, 2);
            $erShare = round($msc * 0.10, 2);
            $ec = ($msc >= 15000.00) ? 30.00 : 10.00;
        }

        if ($isSemiMonthly) {
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
