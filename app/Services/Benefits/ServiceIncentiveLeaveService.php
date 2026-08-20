<?php

declare(strict_types=1);

namespace App\Services\Benefits;

use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\ServiceIncentiveLeave;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServiceIncentiveLeaveService
{
    /**
     * Get or initialize the annual SIL record for an employee under DOLE Art. 95.
     */
    public function getOrCreateAnnualRecord(Employee $employee, int $year): ServiceIncentiveLeave
    {
        $standardDays = (int) CompanySetting::getValue('sil_annual_days', 5);
        $minTenureYears = (float) CompanySetting::getValue('sil_min_tenure_years', 1.0);

        $tenureYears = $employee->years_of_service;
        $isEntitled = $tenureYears >= $minTenureYears && $employee->employment_status !== 'terminated';
        $entitledDays = $isEntitled ? $standardDays : 0;

        return ServiceIncentiveLeave::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'year' => $year,
            ],
            [
                'entitled_days' => $entitledDays,
                'used_days' => 0,
                'cash_converted_days' => 0,
                'cash_converted_amount' => 0.00,
                'status' => 'active',
                'leave_logs' => [],
            ]
        );
    }

    /**
     * Record leave days taken by an employee.
     */
    public function logLeaveUsage(Employee $employee, int $year, int $daysTaken, string $leaveDate, ?string $notes = null): ServiceIncentiveLeave
    {
        return DB::transaction(function () use ($employee, $year, $daysTaken, $leaveDate, $notes) {
            $record = $this->getOrCreateAnnualRecord($employee, $year);

            $availableDays = $record->remaining_days;
            $actualDays = min($daysTaken, $availableDays);

            $logs = $record->leave_logs ?? [];
            $logs[] = [
                'date' => $leaveDate,
                'days' => $actualDays,
                'notes' => $notes ?: 'Service Incentive Leave (DOLE Art. 95)',
                'logged_at' => now()->toIso8601String(),
            ];

            $record->used_days += $actualDays;
            $record->leave_logs = $logs;
            $record->notes = $notes ? trim(($record->notes ? $record->notes . " | " : '') . "Logged {$actualDays}d on {$leaveDate}: {$notes}") : $record->notes;
            $record->save();

            return $record;
        });
    }

    /**
     * Convert unused SIL leave balance into cash compensation (DOLE Art. 95).
     */
    public function convertUnusedToCash(Employee $employee, int $year, ?int $daysToConvert = null): ServiceIncentiveLeave
    {
        return DB::transaction(function () use ($employee, $year, $daysToConvert) {
            $record = $this->getOrCreateAnnualRecord($employee, $year);

            $availableDays = $record->remaining_days;
            $targetDays = ($daysToConvert !== null && $daysToConvert > 0)
                ? min($daysToConvert, $availableDays)
                : $availableDays;

            if ($targetDays <= 0) {
                return $record;
            }

            $dailyRate = (float) ($employee->daily_rate ?: ($employee->monthly_rate ? $employee->monthly_rate / 26 : 0.00));
            $cashValue = round($targetDays * $dailyRate, 2);

            $record->cash_converted_days += $targetDays;
            $record->cash_converted_amount += $cashValue;

            if ($record->remaining_days === 0) {
                $record->status = 'converted';
            }

            $record->notes = trim(($record->notes ? $record->notes . " | " : '') . "Converted {$targetDays} days to PHP {$cashValue} on " . now()->format('Y-m-d'));
            $record->save();

            return $record;
        });
    }

    /**
     * Batch reset / initialize SIL pool for an entire calendar year.
     */
    public function resetAnnualPool(int $newYear): array
    {
        $activeEmployees = Employee::where('employment_status', '!=', 'terminated')->get();
        $processedCount = 0;
        $entitledCount = 0;

        foreach ($activeEmployees as $emp) {
            $record = $this->getOrCreateAnnualRecord($emp, $newYear);
            $processedCount++;
            if ($record->entitled_days > 0) {
                $entitledCount++;
            }
        }

        return [
            'year' => $newYear,
            'total_processed' => $processedCount,
            'total_entitled' => $entitledCount,
        ];
    }

    /**
     * Fetch full SIL summary statistics and paginated roster for a given year.
     */
    public function getRosterData(int $year, ?string $search = null, ?string $departmentId = null, int $perPage = 15): array
    {
        $query = Employee::with(['department', 'serviceIncentiveLeaves' => fn ($q) => $q->where('year', $year)])
            ->orderBy('first_name');

        if ($search) {
            $query->search($search);
        }

        if ($departmentId && $departmentId !== 'all') {
            $query->department($departmentId);
        }

        /** @var LengthAwarePaginator $employees */
        $employees = $query->paginate($perPage)->withQueryString();

        $standardSilDays = (int) CompanySetting::getValue('sil_annual_days', 5);

        $roster = $employees->getCollection()->map(function (Employee $emp) use ($year, $standardSilDays) {
            $record = $emp->serviceIncentiveLeaves->first() ?: $this->getOrCreateAnnualRecord($emp, $year);

            $dailyRate = (float) ($emp->daily_rate ?: ($emp->monthly_rate ? $emp->monthly_rate / 26 : 0.00));
            $cashValue = round($record->remaining_days * $dailyRate, 2);

            $lastLog = ! empty($record->leave_logs) ? end($record->leave_logs) : null;

            return [
                'employee' => $emp,
                'record' => $record,
                'tenure_years' => $emp->years_of_service,
                'tenure_text' => $emp->tenure_text,
                'is_entitled' => $record->entitled_days > 0,
                'entitled_days' => $record->entitled_days,
                'used_days' => $record->used_days,
                'cash_converted_days' => $record->cash_converted_days,
                'cash_converted_amount' => $record->cash_converted_amount,
                'remaining_days' => $record->remaining_days,
                'daily_rate' => $dailyRate,
                'cash_conversion_value' => $cashValue,
                'last_leave_date' => $lastLog['date'] ?? null,
                'status' => $record->status,
            ];
        });

        // Global stats for active employees in this year
        $allActive = Employee::where('employment_status', '!=', 'terminated')->get();
        $entitledActiveCount = $allActive->filter(fn (Employee $e) => $e->years_of_service >= 1.0)->count();
        $totalSilPoolDays = $entitledActiveCount * $standardSilDays;

        $existingRecords = ServiceIncentiveLeave::where('year', $year)->get();
        $totalSilDaysUsed = (int) $existingRecords->sum('used_days');
        $totalSilDaysConverted = (int) $existingRecords->sum('cash_converted_days');
        $totalConvertedAmount = (float) $existingRecords->sum('cash_converted_amount');
        $totalSilDaysRemaining = max(0, $totalSilPoolDays - $totalSilDaysUsed - $totalSilDaysConverted);

        $stats = [
            'year' => $year,
            'total_active' => $allActive->count(),
            'entitled_sil_count' => $entitledActiveCount,
            'total_sil_pool_days' => $totalSilPoolDays,
            'total_sil_days_used' => $totalSilDaysUsed,
            'total_sil_days_converted' => $totalSilDaysConverted,
            'total_converted_amount' => $totalConvertedAmount,
            'total_sil_days_remaining' => $totalSilDaysRemaining,
            'standard_sil_days' => $standardSilDays,
        ];

        return [
            'employees' => $employees,
            'silRoster' => $roster,
            'stats' => $stats,
        ];
    }
}
