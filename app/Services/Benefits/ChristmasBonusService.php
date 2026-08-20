<?php

declare(strict_types=1);

namespace App\Services\Benefits;

use App\Models\ChristmasBonusDisbursement;
use App\Models\CompanySetting;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ChristmasBonusService
{
    /**
     * Compute Christmas Bonus amount and pro-rating status for an employee.
     */
    public function calculateForEmployee(
        Employee $employee,
        int $year,
        ?float $overrideBaseAmount = null,
        ?int $overrideMinMonths = null
    ): array {
        $baseAmount = $overrideBaseAmount !== null
            ? $overrideBaseAmount
            : (float) CompanySetting::getValue('christmas_bonus_amount', 2000.00);

        $minMonths = $overrideMinMonths !== null
            ? $overrideMinMonths
            : (int) CompanySetting::getValue('christmas_bonus_min_months', 6);

        if (! $employee->hire_date || $employee->employment_status === 'terminated') {
            return [
                'employee' => $employee,
                'base_bonus_amount' => $baseAmount,
                'months_tenure' => 0.0,
                'is_prorated' => false,
                'is_qualified' => false,
                'calculated_bonus_amount' => 0.00,
            ];
        }

        $yearEnd = Carbon::createFromDate($year, 12, 31);
        $evaluationDate = ($year === (int) date('Y')) ? now() : $yearEnd;
        $hireDate = Carbon::parse($employee->hire_date);

        if ($hireDate->isAfter($evaluationDate)) {
            return [
                'employee' => $employee,
                'base_bonus_amount' => $baseAmount,
                'months_tenure' => 0.0,
                'is_prorated' => false,
                'is_qualified' => false,
                'calculated_bonus_amount' => 0.00,
            ];
        }

        // Total months between hire date and evaluation date
        $monthsTenure = round((float) ($hireDate->diffInDays($evaluationDate) / 30.4375), 1);

        if ($monthsTenure >= $minMonths) {
            $calculatedBonus = $baseAmount;
            $isProrated = false;
            $isQualified = true;
        } elseif ($monthsTenure >= 1.0) {
            $monthsInYear = min(12.0, $monthsTenure);
            $calculatedBonus = round(($monthsInYear / 12.0) * $baseAmount, 2);
            $isProrated = true;
            $isQualified = true;
        } else {
            $calculatedBonus = 0.00;
            $isProrated = false;
            $isQualified = false;
        }

        return [
            'employee' => $employee,
            'base_bonus_amount' => $baseAmount,
            'months_tenure' => $monthsTenure,
            'is_prorated' => $isProrated,
            'is_qualified' => $isQualified,
            'calculated_bonus_amount' => $calculatedBonus,
        ];
    }

    /**
     * Batch generate Christmas Bonus allocation records for a calendar year.
     */
    public function batchGenerateForYear(
        int $year,
        ?float $overrideBaseAmount = null,
        ?int $overrideMinMonths = null
    ): array {
        $employees = Employee::where('employment_status', '!=', 'terminated')->get();
        $generatedCount = 0;
        $totalOutlay = 0.00;

        DB::transaction(function () use ($employees, $year, $overrideBaseAmount, $overrideMinMonths, &$generatedCount, &$totalOutlay) {
            foreach ($employees as $emp) {
                $calc = $this->calculateForEmployee($emp, $year, $overrideBaseAmount, $overrideMinMonths);

                ChristmasBonusDisbursement::updateOrCreate(
                    [
                        'employee_id' => $emp->id,
                        'bonus_year' => $year,
                    ],
                    [
                        'base_bonus_amount' => $calc['base_bonus_amount'],
                        'months_tenure' => $calc['months_tenure'],
                        'is_prorated' => $calc['is_prorated'],
                        'calculated_bonus_amount' => $calc['calculated_bonus_amount'],
                        'status' => 'pending',
                        'notes' => $calc['is_prorated']
                            ? "Pro-rated bonus ({$calc['months_tenure']} mos served in Year {$year})."
                            : "Full tenure bonus qualified ({$calc['months_tenure']} mos service).",
                    ]
                );

                $generatedCount++;
                $totalOutlay += $calc['calculated_bonus_amount'];
            }
        });

        return [
            'year' => $year,
            'total_generated' => $generatedCount,
            'total_outlay' => $totalOutlay,
        ];
    }

    /**
     * Approve pending Christmas Bonus allocation batch.
     */
    public function approveBatch(int $year): int
    {
        return ChristmasBonusDisbursement::where('bonus_year', $year)
            ->where('status', 'pending')
            ->update(['status' => 'hr_approved']);
    }

    /**
     * Release approved Christmas Bonus batch to payroll.
     */
    public function releaseBatchToPayroll(int $year): int
    {
        return ChristmasBonusDisbursement::where('bonus_year', $year)
            ->where('status', 'hr_approved')
            ->update([
                'status' => 'released_to_payroll',
                'released_at' => now(),
            ]);
    }

    /**
     * Fetch Christmas Bonus roster, tenure calculations, and statistics for a given year.
     */
    public function getRosterAndDisbursementData(
        int $year,
        ?string $search = null,
        ?string $departmentId = null,
        int $perPage = 15
    ): array {
        $query = Employee::with(['department', 'christmasBonusDisbursements' => fn ($q) => $q->where('bonus_year', $year)])
            ->where('employment_status', '!=', 'terminated')
            ->orderBy('first_name');

        if ($search) {
            $query->search($search);
        }

        if ($departmentId && $departmentId !== 'all') {
            $query->department($departmentId);
        }

        /** @var LengthAwarePaginator $employees */
        $employees = $query->paginate($perPage)->withQueryString();

        $standardBonus = (float) CompanySetting::getValue('christmas_bonus_amount', 2000.00);
        $minMonths = (int) CompanySetting::getValue('christmas_bonus_min_months', 6);

        $roster = $employees->getCollection()->map(function (Employee $emp) use ($year, $standardBonus, $minMonths) {
            $disb = $emp->christmasBonusDisbursements->first();

            if ($disb) {
                $baseAmount = $disb->base_bonus_amount;
                $monthsTenure = $disb->months_tenure;
                $isProrated = $disb->is_prorated;
                $calculatedBonus = $disb->calculated_bonus_amount;
                $status = $disb->status;
                $isDisbursed = $disb->status === 'released_to_payroll';
                $isQualified = $calculatedBonus > 0;
            } else {
                $calc = $this->calculateForEmployee($emp, $year, $standardBonus, $minMonths);
                $baseAmount = $calc['base_bonus_amount'];
                $monthsTenure = $calc['months_tenure'];
                $isProrated = $calc['is_prorated'];
                $calculatedBonus = $calc['calculated_bonus_amount'];
                $status = 'unprocessed';
                $isDisbursed = false;
                $isQualified = $calc['is_qualified'];
            }

            return [
                'employee' => $emp,
                'disbursement' => $disb,
                'base_bonus_amount' => $baseAmount,
                'months_tenure' => $monthsTenure,
                'is_prorated' => $isProrated,
                'is_qualified' => $isQualified,
                'calculated_bonus_amount' => $calculatedBonus,
                'status' => $status,
                'is_disbursed' => $isDisbursed,
            ];
        });

        // Global stats for this year
        $allActive = Employee::where('employment_status', '!=', 'terminated')->get();
        $disbursements = ChristmasBonusDisbursement::where('bonus_year', $year)->get();

        $qualifiedCount = 0;
        $totalProjectedOutlay = 0.00;

        foreach ($allActive as $emp) {
            $calc = $this->calculateForEmployee($emp, $year, $standardBonus, $minMonths);
            if ($calc['is_qualified']) {
                $qualifiedCount++;
                $totalProjectedOutlay += $calc['calculated_bonus_amount'];
            }
        }

        $stats = [
            'year' => $year,
            'total_active' => $allActive->count(),
            'qualified_count' => $qualifiedCount,
            'christmas_bonus_amount' => $standardBonus,
            'christmas_bonus_min_months' => $minMonths,
            'christmas_bonus_enabled' => (bool) CompanySetting::getValue('christmas_bonus_enabled', true),
            'total_disbursements_count' => $disbursements->count(),
            'total_actual_outlay' => (float) $disbursements->sum('calculated_bonus_amount'),
            'total_projected_outlay' => $totalProjectedOutlay,
            'pending_count' => $disbursements->where('status', 'pending')->count(),
            'approved_count' => $disbursements->where('status', 'hr_approved')->count(),
            'released_count' => $disbursements->where('status', 'released_to_payroll')->count(),
        ];

        return [
            'employees' => $employees,
            'roster' => $roster,
            'stats' => $stats,
        ];
    }
}
