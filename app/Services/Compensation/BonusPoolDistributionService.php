<?php

declare(strict_types=1);

namespace App\Services\Compensation;

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\PerformanceBonus;
use App\Services\FinancialService;
use Illuminate\Support\Facades\DB;

class BonusPoolDistributionService
{
    public function __construct(
        protected FinancialService $financialService,
        protected CounterOfferService $counterOfferService
    ) {}

    /**
     * Calculate Proportional Weighted Distribution of Bonus Pool across Eligible Employees (known.md §6.10 & Section 2.11)
     *
     * @return array<string, mixed>
     */
    public function calculateDistribution(
        float $poolAmount,
        ?int $departmentId = null,
        string $bonusType = 'performance'
    ): array {
        $query = Employee::with('department')
            ->where('employment_status', '!=', 'resigned');

        if ($departmentId && $departmentId > 0) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();
        $headcount = $employees->count();

        if ($headcount === 0 || $poolAmount <= 0) {
            return [
                'pool_amount' => $poolAmount,
                'department_id' => $departmentId,
                'bonus_type' => $bonusType,
                'headcount' => 0,
                'total_weight' => 0,
                'allocations' => [],
                'total_allocated' => 0.00,
                'budget_check' => ['approved' => true, 'reason' => 'No allocation'],
            ];
        }

        $totalWeight = 0.0;
        $employeeWeights = [];

        foreach ($employees as $emp) {
            $isDriver = str_contains(strtolower($emp->position ?? ''), 'driver');
            $baseSalary = (float) ($emp->monthly_rate ?: ($emp->daily_rate ? $emp->daily_rate * 26 : ($isDriver ? 28000.00 : 25000.00)));

            $rating = $emp->performance_rating ?? 'Satisfactory';
            $score = $this->resolveScoreFromRating($rating);

            $perfMultiplier = match (true) {
                $score >= 5.0 => 1.50,
                $score >= 4.0 => 1.25,
                $score >= 3.0 => 1.00,
                $score >= 2.0 => 0.50,
                default => 0.00,
            };

            $tenureYears = (float) ($emp->years_of_service ?? 1.0);
            $tenureFactor = min(2.0, 1.0 + ($tenureYears * 0.05));

            $weight = match ($bonusType) {
                'tenure_milestone' => round($baseSalary * $tenureFactor, 2),
                'fourteenth_month', 'mid_year' => round($baseSalary * ($perfMultiplier > 0 ? 1.0 : 0.5), 2),
                default => round($baseSalary * $perfMultiplier * $tenureFactor, 2),
            };

            $totalWeight += $weight;
            $employeeWeights[] = [
                'employee' => $emp,
                'base_salary' => $baseSalary,
                'rating' => $rating,
                'score' => $score,
                'perf_multiplier' => $perfMultiplier,
                'tenure_years' => $tenureYears,
                'weight' => $weight,
            ];
        }

        $allocations = [];
        $runningTotal = 0.0;

        foreach ($employeeWeights as $item) {
            $emp = $item['employee'];
            $share = $totalWeight > 0 ? round(($item['weight'] / $totalWeight) * $poolAmount, 2) : round($poolAmount / $headcount, 2);
            $runningTotal += $share;

            $allocations[] = [
                'employee_id' => $emp->id,
                'employee_name' => "{$emp->first_name} {$emp->last_name}",
                'employee_code' => $emp->employee_code,
                'department' => $emp->department?->name ?? 'Operations',
                'position' => $emp->position,
                'base_salary' => $item['base_salary'],
                'performance_rating' => $item['rating'],
                'performance_score' => $item['score'],
                'weight' => $item['weight'],
                'allocated_bonus' => $share,
                'share_percentage' => $poolAmount > 0 ? round(($share / $poolAmount) * 100, 2) : 0.0,
            ];
        }

        // Adjust penny rounding on first non-zero allocation
        $diff = round($poolAmount - $runningTotal, 2);
        if ($diff != 0 && count($allocations) > 0) {
            $allocations[0]['allocated_bonus'] = round($allocations[0]['allocated_bonus'] + $diff, 2);
            $runningTotal = round($runningTotal + $diff, 2);
        }

        $deptName = $departmentId ? (Department::find($departmentId)?->name ?? 'Operations') : 'Operations';
        $budgetCheck = $this->financialService->checkBudgetAvailability($poolAmount, $deptName);

        return [
            'pool_amount' => $poolAmount,
            'department_id' => $departmentId,
            'department_name' => $departmentId ? (Department::find($departmentId)?->name ?? 'All Departments') : 'Company-Wide',
            'bonus_type' => $bonusType,
            'headcount' => $headcount,
            'total_weight' => round($totalWeight, 2),
            'total_allocated' => $runningTotal,
            'allocations' => $allocations,
            'budget_check' => $budgetCheck,
            'formula' => "Individual Share = (Weight / Total Weight PHP " . number_format($totalWeight, 2) . ") x Total Pool PHP " . number_format($poolAmount, 2),
        ];
    }

    /**
     * Commit and Persist Bonus Allocation to Employee Records and Compensation Adjustments
     *
     * @param array<int, array<string, mixed>> $allocations
     */
    public function commitBonusAllocation(
        float $poolAmount,
        ?int $departmentId = null,
        string $bonusType = 'performance',
        array $allocations = []
    ): bool {
        return DB::transaction(function () use ($poolAmount, $departmentId, $bonusType, $allocations) {
            if (empty($allocations)) {
                $calc = $this->calculateDistribution($poolAmount, $departmentId, $bonusType);
                $allocations = $calc['allocations'];
            }

            foreach ($allocations as $item) {
                $bonusVal = (float) ($item['allocated_bonus'] ?? ($item['bonus_amount'] ?? 0.0));
                if ($bonusVal <= 0) continue;

                $emp = Employee::find($item['employee_id']);
                if (! $emp) continue;

                $currentSalary = (float) $emp->monthly_rate;

                CompensationAdjustment::create([
                    'employee_id' => $emp->id,
                    'subject_type' => 'employee',
                    'type' => 'performance_bonus',
                    'mode' => 'mode_a',
                    'old_rate' => $currentSalary,
                    'new_rate' => $currentSalary,
                    'bonus_amount' => $bonusVal,
                    'old_position' => $emp->position,
                    'new_position' => $emp->position,
                    'status' => 'approved',
                    'budget_impact_status' => 'BUDGET_APPROVED',
                    'reason' => "Allocated {$bonusType} bonus share of PHP " . number_format($bonusVal, 2) . " from total pool of PHP " . number_format($poolAmount, 2),
                    'effective_date' => now(),
                ]);

                PerformanceBonus::create([
                    'employee_id' => $emp->id,
                    'cutoff_period' => now()->format('Y-m-01 to Y-m-15'),
                    'bonus_amount' => $bonusVal,
                    'reason' => "{$bonusType} allocation",
                ]);
            }

            PayrollAuditTrail::create([
                'action' => 'BONUS_POOL_ALLOCATION',
                'model_type' => Department::class,
                'model_id' => $departmentId,
                'user_name' => 'HR Manager',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['pool_amount' => $poolAmount, 'type' => $bonusType],
                'new_values' => ['distributed_headcount' => count($allocations), 'total_distributed' => $poolAmount],
            ]);

            return true;
        });
    }

    protected function resolveScoreFromRating(string $rating): float
    {
        return match (strtolower(trim($rating))) {
            'outstanding', '5.0', '5' => 5.0,
            'very satisfactory', 'exceeds expectations', '4.0', '4.5', '4' => 4.5,
            'satisfactory', 'meets expectations', '3.0', '3.5', '3' => 3.5,
            'needs improvement', '2.0', '2.5', '2' => 2.5,
            'unsatisfactory', '1.0', '1.5', '1' => 1.5,
            default => 3.5,
        };
    }
}
