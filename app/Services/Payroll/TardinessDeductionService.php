<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\Employee;

class TardinessDeductionService
{
    /**
     * Compute Late Tardiness and Undertime Deductions based on exact per-minute rates.
     *
     * @return array{tardiness_deduction: float, undertime_deduction: float, total_time_deductions: float, minute_rate: float}
     */
    public function compute(Employee $employee, ?Attendance $attendance): array
    {
        $dailyRate = (float) ($employee->daily_rate ?: ($employee->monthly_rate ? round($employee->monthly_rate / 26, 2) : 0.00));
        $hourlyRate = $dailyRate > 0 ? round($dailyRate / 8, 2) : 0.00;
        $minuteRate = $hourlyRate > 0 ? round($hourlyRate / 60, 4) : 0.00;

        if (! $attendance || $minuteRate <= 0) {
            return [
                'tardiness_deduction' => 0.00,
                'undertime_deduction' => 0.00,
                'total_time_deductions' => 0.00,
                'minute_rate' => 0.00,
            ];
        }

        $tardyMinutes = (int) $attendance->tardiness_minutes;
        $undertimeMinutes = (int) $attendance->undertime_minutes;

        // If legacy lates_count is present but tardiness_minutes is 0, estimate 15 mins per late
        if ($tardyMinutes === 0 && ($attendance->lates_count ?? 0) > 0) {
            $tardyMinutes = $attendance->lates_count * 15;
        }

        $tardyDeduction = round($minuteRate * $tardyMinutes, 2);
        $undertimeDeduction = round($minuteRate * $undertimeMinutes, 2);

        return [
            'tardiness_deduction' => $tardyDeduction,
            'undertime_deduction' => $undertimeDeduction,
            'total_time_deductions' => round($tardyDeduction + $undertimeDeduction, 2),
            'minute_rate' => $minuteRate,
        ];
    }
}
