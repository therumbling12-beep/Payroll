<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;

class TardinessDeductionService
{
    /**
     * Compute Late Tardiness and Undertime Deductions based on docs/no.md (Daily Rate / 8 * hours late).
     *
     * @return array{
     *     tardiness_deduction: float,
     *     undertime_deduction: float,
     *     total_time_deductions: float,
     *     hourly_rate: float,
     *     minute_rate: float,
     *     tardy_hours: float,
     *     undertime_hours: float
     * }
     */
    public function compute(Employee $employee, ?Attendance $attendance): array
    {
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $workingHours = (float) CompanySetting::getValue('standard_working_hours_per_day', 8.0);
        $defaultLateMins = (int) CompanySetting::getValue('tardiness_default_minutes_per_late', 15);
        $basis = (string) CompanySetting::getValue('tardiness_deduction_basis', 'hourly');

        $dailyRate = (float) ($employee->daily_rate ?: ($employee->monthly_rate ? round($employee->monthly_rate / $workingDays, 2) : 0.00));
        $hourlyRate = $dailyRate > 0 ? round($dailyRate / $workingHours, 2) : 0.00;
        $minuteRate = $hourlyRate > 0 ? round($hourlyRate / 60, 4) : 0.00;

        if (! $attendance || $hourlyRate <= 0) {
            return [
                'tardiness_deduction' => 0.00,
                'undertime_deduction' => 0.00,
                'total_time_deductions' => 0.00,
                'hourly_rate' => $hourlyRate,
                'minute_rate' => $minuteRate,
                'tardy_hours' => 0.0,
                'undertime_hours' => 0.0,
            ];
        }

        $tardyMinutes = (int) $attendance->tardiness_minutes;
        $undertimeMinutes = (int) $attendance->undertime_minutes;

        // If legacy lates_count is present but tardiness_minutes is 0, estimate configured mins per late
        if ($tardyMinutes === 0 && ($attendance->lates_count ?? 0) > 0) {
            $tardyMinutes = $attendance->lates_count * $defaultLateMins;
        }

        $tardyHours = (float) ($attendance->late_hours ?? ($tardyMinutes > 0 ? round($tardyMinutes / 60, 2) : 0.0));
        $undertimeHours = (float) ($attendance->undertime_hours ?? ($undertimeMinutes > 0 ? round($undertimeMinutes / 60, 2) : 0.0));

        if ($basis === 'minute') {
            $tardyDeduction = round($minuteRate * $tardyMinutes, 2);
            $undertimeDeduction = round($minuteRate * $undertimeMinutes, 2);
        } else {
            // docs/no.md Line 80 & 81: Daily rate ÷ 8 × # of hrs. late (hourly basis)
            $tardyDeduction = round($hourlyRate * $tardyHours, 2);
            $undertimeDeduction = round($hourlyRate * $undertimeHours, 2);
        }

        return [
            'tardiness_deduction' => $tardyDeduction,
            'undertime_deduction' => $undertimeDeduction,
            'total_time_deductions' => round($tardyDeduction + $undertimeDeduction, 2),
            'hourly_rate' => $hourlyRate,
            'minute_rate' => $minuteRate,
            'tardy_hours' => $tardyHours,
            'undertime_hours' => $undertimeHours,
        ];
    }
}
