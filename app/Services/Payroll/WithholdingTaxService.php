<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\GovernmentContributionTable;

class WithholdingTaxService
{
    /**
     * Compute BIR Withholding Tax based on TRAIN Law (RA 10963) graduated tax tables.
     *
     * @param float $taxableIncome Taxable income for the period (Gross minus EE statutory deductions)
     * @param bool $isSemiMonthly Whether the period is semi-monthly (x24 annual factor) or monthly (x12)
     */
    public function compute(float $taxableIncome, bool $isSemiMonthly = true): float
    {
        if ($taxableIncome <= 0) {
            return 0.00;
        }

        // Annualize taxable compensation
        $multiplier = $isSemiMonthly ? 24 : 12;
        $annualTaxable = $taxableIncome * $multiplier;

        if ($annualTaxable <= 250000.00) {
            return 0.00;
        }

        $bracket = GovernmentContributionTable::where('table_type', 'BIR_TRAIN')
            ->where('bracket_from', '<=', $annualTaxable)
            ->where(function ($query) use ($annualTaxable) {
                $query->where('bracket_to', '>=', $annualTaxable)
                    ->orWhereNull('bracket_to');
            })
            ->first();

        if ($bracket) {
            $baseTax = (float) $bracket->base_tax;
            $excessRate = (float) $bracket->excess_rate;
            $bracketFrom = (float) $bracket->bracket_from;
            $annualTax = $baseTax + (($annualTaxable - $bracketFrom) * $excessRate);
        } else {
            // Heuristic graduated table calculation
            if ($annualTaxable <= 400000.00) {
                $annualTax = ($annualTaxable - 250000.00) * 0.15;
            } elseif ($annualTaxable <= 800000.00) {
                $annualTax = 22500.00 + (($annualTaxable - 400000.00) * 0.20);
            } elseif ($annualTaxable <= 2000000.00) {
                $annualTax = 102500.00 + (($annualTaxable - 800000.00) * 0.25);
            } elseif ($annualTaxable <= 8000000.00) {
                $annualTax = 402500.00 + (($annualTaxable - 2000000.00) * 0.30);
            } else {
                $annualTax = 2202500.00 + (($annualTaxable - 8000000.00) * 0.35);
            }
        }

        $periodicTax = max(0.00, round($annualTax / $multiplier, 2));

        return $periodicTax;
    }
}
