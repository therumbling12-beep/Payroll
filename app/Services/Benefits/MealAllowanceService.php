<?php

declare(strict_types=1);

namespace App\Services\Benefits;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\MealAllowanceDisbursement;
use App\Models\TripIncome;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MealAllowanceService
{
    /**
     * Compute meal allowance and BIR De Minimis taxability for a specific employee and cutoff.
     */
    public function computeForEmployee(Employee $employee, string $cutoffPeriod, ?float $overrideDailyRate = null): array
    {
        $dailyRate = $overrideDailyRate !== null
            ? $overrideDailyRate
            : (float) CompanySetting::getValue('meal_allowance_daily', 60.00);

        $weeklyCap = (float) CompanySetting::getValue('meal_de_minimis_weekly_cap', 500.00);

        // 1. Look for logged attendance shifts
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('cutoff_period', $cutoffPeriod)
            ->first();

        if ($attendance && $attendance->days_worked > 0) {
            $daysRendered = (int) $attendance->days_worked;
        } else {
            // 2. Look for trip income logs (TNVS Driver volume)
            $tripIncome = TripIncome::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->first();

            if ($tripIncome && $tripIncome->total_trips > 0) {
                $daysRendered = min(6, max(1, (int) ceil($tripIncome->total_trips / 4)));
            } else {
                // 3. Fallback to standard work week (6 for drivers, 5 for office staff)
                $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
                $daysRendered = $isDriver ? 6 : 5;
            }
        }

        $grossAmount = round($daysRendered * $dailyRate, 2);
        $taxExemptAmount = min($grossAmount, $weeklyCap);
        $taxableExcessAmount = max(0.00, round($grossAmount - $taxExemptAmount, 2));

        return [
            'days_rendered' => $daysRendered,
            'daily_subsidy_rate' => $dailyRate,
            'gross_amount' => $grossAmount,
            'tax_exempt_amount' => $taxExemptAmount,
            'taxable_excess_amount' => $taxableExcessAmount,
        ];
    }

    /**
     * Batch generate meal allowance disbursement records for a cutoff period.
     */
    public function batchGenerateForCutoff(string $cutoffPeriod, ?float $overrideDailyRate = null, ?int $departmentId = null): array
    {
        $query = Employee::where('employment_status', '!=', 'terminated');
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();
        $generatedCount = 0;
        $totalGrossOutlay = 0.00;

        DB::transaction(function () use ($employees, $cutoffPeriod, $overrideDailyRate, &$generatedCount, &$totalGrossOutlay) {
            foreach ($employees as $emp) {
                $comp = $this->computeForEmployee($emp, $cutoffPeriod, $overrideDailyRate);

                MealAllowanceDisbursement::updateOrCreate(
                    [
                        'employee_id' => $emp->id,
                        'cutoff_period' => $cutoffPeriod,
                    ],
                    [
                        'days_rendered' => $comp['days_rendered'],
                        'daily_subsidy_rate' => $comp['daily_subsidy_rate'],
                        'gross_amount' => $comp['gross_amount'],
                        'tax_exempt_amount' => $comp['tax_exempt_amount'],
                        'taxable_excess_amount' => $comp['taxable_excess_amount'],
                        'status' => 'pending',
                        'notes' => "Auto-computed for {$cutoffPeriod} based on {$comp['days_rendered']} shifts rendered.",
                    ]
                );

                $generatedCount++;
                $totalGrossOutlay += $comp['gross_amount'];
            }
        });

        return [
            'cutoff_period' => $cutoffPeriod,
            'total_generated' => $generatedCount,
            'total_gross_outlay' => $totalGrossOutlay,
        ];
    }

    /**
     * Approve pending meal allowance disbursement batch for a cutoff period.
     */
    public function approveBatch(string $cutoffPeriod): int
    {
        return MealAllowanceDisbursement::where('cutoff_period', $cutoffPeriod)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);
    }

    /**
     * Release approved meal allowance batch and mark as disbursed to payroll.
     */
    public function releaseBatchToPayroll(string $cutoffPeriod): int
    {
        return MealAllowanceDisbursement::where('cutoff_period', $cutoffPeriod)
            ->where('status', 'approved')
            ->update([
                'status' => 'released_to_payroll',
                'disbursed_at' => now(),
            ]);
    }

    /**
     * Fetch meal allowance roster, live calculations, and cutoff statistics.
     */
    public function getRosterAndDisbursementData(?string $cutoffPeriod = null, ?string $search = null, ?string $departmentId = null, int $perPage = 15): array
    {
        $activeCutoff = $cutoffPeriod ?: (string) CompanySetting::getValue('payroll_current_cutoff', date('Y-m-d'));

        $query = Employee::with(['department', 'mealAllowanceDisbursements' => fn ($q) => $q->where('cutoff_period', $activeCutoff)])
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

        $mealDailyRate = (float) CompanySetting::getValue('meal_allowance_daily', 60.00);
        $weeklyCap = (float) CompanySetting::getValue('meal_de_minimis_weekly_cap', 500.00);

        $roster = $employees->getCollection()->map(function (Employee $emp) use ($activeCutoff, $mealDailyRate) {
            $disbursement = $emp->mealAllowanceDisbursements->first();

            if ($disbursement) {
                $daysRendered = $disbursement->days_rendered;
                $rate = $disbursement->daily_subsidy_rate;
                $gross = $disbursement->gross_amount;
                $taxExempt = $disbursement->tax_exempt_amount;
                $taxableExcess = $disbursement->taxable_excess_amount;
                $status = $disbursement->status;
                $isDisbursed = $disbursement->status === 'released_to_payroll';
            } else {
                $comp = $this->computeForEmployee($emp, $activeCutoff, $mealDailyRate);
                $daysRendered = $comp['days_rendered'];
                $rate = $comp['daily_subsidy_rate'];
                $gross = $comp['gross_amount'];
                $taxExempt = $comp['tax_exempt_amount'];
                $taxableExcess = $comp['taxable_excess_amount'];
                $status = 'unprocessed';
                $isDisbursed = false;
            }

            return [
                'employee' => $emp,
                'disbursement' => $disbursement,
                'is_driver' => $emp->isDriver(),
                'days_rendered' => $daysRendered,
                'daily_rate' => $rate,
                'gross_amount' => $gross,
                'tax_exempt_amount' => $taxExempt,
                'taxable_excess_amount' => $taxableExcess,
                'status' => $status,
                'is_disbursed' => $isDisbursed,
            ];
        });

        // Global stats for this cutoff
        $disbursements = MealAllowanceDisbursement::where('cutoff_period', $activeCutoff)->get();
        $totalActive = Employee::where('employment_status', '!=', 'terminated')->count();
        $totalDrivers = Employee::where('employment_status', '!=', 'terminated')->drivers()->count();

        $stats = [
            'cutoff_period' => $activeCutoff,
            'total_active' => $totalActive,
            'total_drivers' => $totalDrivers,
            'meal_daily_rate' => $mealDailyRate,
            'meal_de_minimis_weekly_cap' => $weeklyCap,
            'total_disbursements_count' => $disbursements->count(),
            'total_gross_outlay' => (float) $disbursements->sum('gross_amount'),
            'total_tax_exempt' => (float) $disbursements->sum('tax_exempt_amount'),
            'total_taxable_excess' => (float) $disbursements->sum('taxable_excess_amount'),
            'pending_count' => $disbursements->where('status', 'pending')->count(),
            'approved_count' => $disbursements->where('status', 'approved')->count(),
            'released_count' => $disbursements->where('status', 'released_to_payroll')->count(),
            'est_weekly_outlay' => $totalDrivers * ($mealDailyRate * 6),
            'est_monthly_outlay' => $totalDrivers * ($mealDailyRate * 6) * 4.33,
        ];

        return [
            'employees' => $employees,
            'roster' => $roster,
            'stats' => $stats,
            'activeCutoff' => $activeCutoff,
        ];
    }
}
