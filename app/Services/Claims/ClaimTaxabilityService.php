<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Employee;
use Carbon\Carbon;

class ClaimTaxabilityService
{
    /**
     * Default statutory annual cap for Medical Assistance de minimis benefit (BIR TRAIN Law)
     */
    public const DEFAULT_MEDICAL_DE_MINIMIS_CAP = 10000.00;

    /**
     * Get statutory medical de minimis annual cap from company settings or statutory default
     */
    public function getMedicalDeMinimisCap(): float
    {
        return (float) CompanySetting::getValue('medical_de_minimis_annual_cap', self::DEFAULT_MEDICAL_DE_MINIMIS_CAP);
    }

    /**
     * Classify and compute the statutory tax breakdown for a claim under BIR TRAIN Law
     *
     * @return array{
     *     tax_classification: string,
     *     tax_label: string,
     *     claim_amount: float,
     *     non_taxable_amount: float,
     *     taxable_amount: float,
     *     annual_cap: ?float,
     *     annual_utilized_prior: float,
     *     annual_remaining: ?float,
     *     is_capped: bool,
     *     exceeds_cap: bool,
     *     formula_explanation: string
     * }
     */
    public function classifyClaim(
        ClaimCategory $category,
        float $amount,
        ?Employee $employee = null,
        ?int $year = null
    ): array {
        $year = $year ?? (int) Carbon::now()->year;
        $amount = round($amount, 2);
        $taxClass = $category->tax_classification ?? 'non_taxable';

        // 1. Fully Non-Taxable Business Expense Reimbursement
        if ($taxClass === 'non_taxable') {
            return [
                'tax_classification' => 'non_taxable',
                'tax_label' => 'Non-Taxable Reimbursement (100% Tax Exempt)',
                'claim_amount' => $amount,
                'non_taxable_amount' => $amount,
                'taxable_amount' => 0.00,
                'annual_cap' => null,
                'annual_utilized_prior' => 0.00,
                'annual_remaining' => null,
                'is_capped' => false,
                'exceeds_cap' => false,
                'formula_explanation' => 'Official business expense supported by receipts is 100% non-taxable per BIR regulations.',
            ];
        }

        // 2. Fully Taxable Compensation (Performance incentives, attendance bonuses)
        if ($taxClass === 'taxable') {
            return [
                'tax_classification' => 'taxable',
                'tax_label' => 'Taxable Compensation (Subject to Withholding Tax)',
                'claim_amount' => $amount,
                'non_taxable_amount' => 0.00,
                'taxable_amount' => $amount,
                'annual_cap' => null,
                'annual_utilized_prior' => 0.00,
                'annual_remaining' => null,
                'is_capped' => false,
                'exceeds_cap' => false,
                'formula_explanation' => 'Cash incentive or performance reward is classified as taxable compensation under BIR TRAIN Law.',
            ];
        }

        // 3. De Minimis Benefit with Annual Ceiling (e.g. Medical Assistance capped at PHP 10,000 / year)
        $annualCap = (float) ($category->de_minimis_annual_cap ?: $this->getMedicalDeMinimisCap());
        $annualUtilized = 0.00;

        if ($employee) {
            $annualUtilized = (float) Claim::where('employee_id', $employee->id)
                ->where('category_id', $category->id)
                ->whereIn('approval_status', ['approved', 'payroll_queued', 'paid'])
                ->whereYear('created_at', $year)
                ->sum('non_taxable_amount');
        }

        $annualRemaining = max(0.00, round($annualCap - $annualUtilized, 2));
        $nonTaxablePortion = min($amount, $annualRemaining);
        $taxablePortion = round($amount - $nonTaxablePortion, 2);
        $exceedsCap = $taxablePortion > 0;

        $explanation = $exceedsCap
            ? sprintf(
                'De Minimis Annual Cap is PHP %s. Employee already utilized PHP %s this year (Remaining exempt limit: PHP %s). Non-taxable portion: PHP %s. Excess taxable amount: PHP %s.',
                number_format($annualCap, 2),
                number_format($annualUtilized, 2),
                number_format($annualRemaining, 2),
                number_format($nonTaxablePortion, 2),
                number_format($taxablePortion, 2)
            )
            : sprintf(
                'De Minimis Annual Cap is PHP %s. Claim of PHP %s is fully within remaining exempt limit (PHP %s remaining after this claim).',
                number_format($annualCap, 2),
                number_format($amount, 2),
                number_format($annualRemaining - $nonTaxablePortion, 2)
            );

        return [
            'tax_classification' => 'de_minimis',
            'tax_label' => 'De Minimis Benefit (Subject to Annual Statutory Cap)',
            'claim_amount' => $amount,
            'non_taxable_amount' => $nonTaxablePortion,
            'taxable_amount' => $taxablePortion,
            'annual_cap' => $annualCap,
            'annual_utilized_prior' => $annualUtilized,
            'annual_remaining' => $annualRemaining,
            'is_capped' => true,
            'exceeds_cap' => $exceedsCap,
            'formula_explanation' => $explanation,
        ];
    }

    /**
     * Get the total medical assistance de minimis non-taxable amount utilized by an employee in a given year
     */
    public function getEmployeeMedicalUtilizedThisYear(?Employee $employee, ?int $year = null): float
    {
        if (! $employee) {
            return 0.00;
        }

        $year = $year ?? (int) Carbon::now()->year;

        $sum = (float) Claim::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->where('type', 'medical')
                    ->orWhereHas('categoryModel', function ($sub) {
                        $sub->where('tax_classification', 'de_minimis');
                    });
            })
            ->whereIn('approval_status', ['approved', 'payroll_queued', 'paid'])
            ->where(function ($q) use ($year) {
                $q->whereYear('expense_date', $year)
                    ->orWhereYear('created_at', $year);
            })
            ->sum('non_taxable_amount');

        return round($sum, 2);
    }
}
