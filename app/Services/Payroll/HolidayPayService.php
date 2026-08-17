<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidayPayService
{
    /**
     * Compute Holiday Pay for an employee based on Labor Code Articles 93 & 94.
     *
     * @return array{holiday_pay: float, details: array<string, mixed>}
     */
    public function compute(Employee $employee, ?Attendance $attendance, string $cutoffPeriod): array
    {
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $workingHours = (float) CompanySetting::getValue('standard_working_hours_per_day', 8.0);
        $regularMultiplier = (float) CompanySetting::getValue('holiday_regular_worked_multiplier', 2.00);
        $specialMultiplier = (float) CompanySetting::getValue('holiday_special_worked_multiplier', 1.30);

        $dailyRate = (float) ($employee->daily_rate ?: ($employee->monthly_rate ? round($employee->monthly_rate / $workingDays, 2) : 0.00));
        $hourlyRate = $dailyRate > 0 ? round($dailyRate / $workingHours, 2) : 0.00;

        if ($dailyRate <= 0) {
            return ['holiday_pay' => 0.00, 'details' => []];
        }

        // Parse cutoff period dates (e.g. "2026-07-01_15" -> "2026-07-01" to "2026-07-15")
        $dates = $this->parseCutoffDates($cutoffPeriod);
        $holidays = Holiday::active()
            ->whereBetween('holiday_date', [$dates['start'], $dates['end']])
            ->get();

        $totalHolidayPay = 0.00;
        $details = [];

        $regularHolidayHours = $attendance ? (float) $attendance->holiday_regular_hours : 0.00;
        $specialHolidayHours = $attendance ? (float) $attendance->holiday_special_hours : 0.00;

        // 1. Worked Regular Holiday (200% = 100% standard + 100% premium on hours worked)
        if ($regularHolidayHours > 0) {
            $workedRegularPay = round($hourlyRate * $regularMultiplier * $regularHolidayHours, 2);
            $totalHolidayPay += $workedRegularPay;
            $details['worked_regular_holiday'] = [
                'hours' => $regularHolidayHours,
                'rate' => $hourlyRate * $regularMultiplier,
                'amount' => $workedRegularPay,
            ];
        }

        // 2. Worked Special Non-Working Day (130% = 30% premium on top of basic)
        if ($specialHolidayHours > 0) {
            $workedSpecialPay = round($hourlyRate * $specialMultiplier * $specialHolidayHours, 2);
            $totalHolidayPay += $workedSpecialPay;
            $details['worked_special_holiday'] = [
                'hours' => $specialHolidayHours,
                'rate' => $hourlyRate * $specialMultiplier,
                'amount' => $workedSpecialPay,
            ];
        }

        // 3. Unworked Regular Holiday for Regular Staff (100% daily rate per unworked regular holiday)
        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
        if (! $isDriver && $regularHolidayHours === 0.0) {
            $regularHolidaysInPeriod = $holidays->where('holiday_type', 'regular');
            foreach ($regularHolidaysInPeriod as $holiday) {
                // If employee is on regular payroll and has attendance in cutoff
                if ($attendance && $attendance->days_worked > 0) {
                    $unworkedPay = round($dailyRate, 2);
                    $totalHolidayPay += $unworkedPay;
                    $details['unworked_regular_holidays'][] = [
                        'name' => $holiday->name,
                        'date' => $holiday->holiday_date->format('Y-m-d'),
                        'amount' => $unworkedPay,
                    ];
                }
            }
        }

        return [
            'holiday_pay' => round($totalHolidayPay, 2),
            'details' => $details,
        ];
    }

    /**
     * Helper to parse cutoff period string into start and end dates.
     *
     * @return array{start: string, end: string}
     */
    public function parseCutoffDates(string $cutoffPeriod): array
    {
        if (str_contains($cutoffPeriod, '_')) {
            [$startPart, $endDay] = explode('_', $cutoffPeriod);
            $yearMonth = substr($startPart, 0, 7);
            $endDate = "{$yearMonth}-" . str_pad($endDay, 2, '0', STR_PAD_LEFT);

            return ['start' => $startPart, 'end' => $endDate];
        }

        return ['start' => '2026-07-01', 'end' => '2026-07-15'];
    }
}
