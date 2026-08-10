<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryGrade;

class CompensationService
{
    public function __construct(
        protected FinancialService $financialService
    ) {}

    /**
     * Calculate projected salary based on years of service and salary grade ranges.
     */
    public function calculateSalaryGrowth(Employee $employee, int $additionalYears): array
    {
        $currentSalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * 26 : 25000.00));
        $grade = SalaryGrade::where('position_name', $employee->position)->first();
        
        $growthRate = $grade ? ($grade->annual_growth_rate / 100) : 0.05;
        $maxSalary = $grade ? $grade->max_salary : ($currentSalary * 2);

        $projectedSalary = $currentSalary;
        for ($i = 0; $i < $additionalYears; $i++) {
            $projectedSalary += ($projectedSalary * $growthRate);
        }

        $projectedSalary = min($projectedSalary, $maxSalary);

        return [
            'employee_id' => $employee->id,
            'current_salary' => $currentSalary,
            'years_added' => $additionalYears,
            'projected_salary' => round($projectedSalary, 2),
            'max_grade_cap' => $maxSalary,
        ];
    }

    /**
     * Automated Credential-Based Counter Offer Computation (Integration with Team 1 Applicant Management).
     */
    public function computeCounterOffer(string $position, int $yearsExperience, int $certificationsCount): array
    {
        $grade = SalaryGrade::where('position_name', $position)->first();
        $baseSalary = $grade ? $grade->min_salary : 25000.00;
        $maxSalary = $grade ? $grade->max_salary : 60000.00;

        $expMult = (float) \App\Models\CompanySetting::getValue('counter_offer_exp_multiplier', 2500.00);
        $certMult = (float) \App\Models\CompanySetting::getValue('counter_offer_cert_multiplier', 3500.00);

        // Formula: Base + (Years Exp * ExpMultiplier) + (Certs * CertMultiplier)
        $experienceBonus = $yearsExperience * $expMult;
        $certBonus = $certificationsCount * $certMult;

        $offeredSalary = min($maxSalary, $baseSalary + $experienceBonus + $certBonus);

        // Check budget availability via FinancialService
        $budgetCheck = $this->financialService->checkBudgetAvailability($offeredSalary, 'Human Resources');

        return [
            'position' => $position,
            'years_experience' => $yearsExperience,
            'certifications_count' => $certificationsCount,
            'base_salary' => $baseSalary,
            'computed_counter_offer' => round($offeredSalary, 2),
            'max_allowed' => $maxSalary,
            'financial_budget_check' => $budgetCheck,
        ];
    }
}
