<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\Employee;

class OvertimePayService
{
    /**
     * Compute Overtime Pay and Night Shift Differential (NSD) based on Labor Code Articles 87 & 90.
     *
     * @return array{overtime_pay: float, night_diff_pay: float, total_overtime: float}
     */
    public function compute(Employee $employee, ?Attendance $attendance): array
    {
        $dailyRate = (float) ($employee->daily_rate ?: ($employee->monthly_rate ? round($employee->monthly_rate / 26, 2) : 0.00));
        $hourlyRate = $dailyRate > 0 ? round($dailyRate / 8, 2) : 0.00;

        if (! $attendance || $hourlyRate <= 0) {
            return [
                'overtime_pay' => 0.00,
                'night_diff_pay' => 0.00,
                'total_overtime' => 0.00,
            ];
        }

        $overtimeHours = (float) $attendance->overtime_hours;
        $restDayHours = (float) $attendance->rest_day_hours;
        $nightDiffHours = (float) $attendance->night_diff_hours;

        // 1. Regular Day Overtime (125% rate)
        $regularOtPay = round($hourlyRate * 1.25 * $overtimeHours, 2);

        // 2. Rest Day Overtime (169% rate)
        $restDayOtPay = round($hourlyRate * 1.69 * $restDayHours, 2);

        $totalOtPay = round($regularOtPay + $restDayOtPay, 2);

        // 3. Night Shift Differential (10% premium for 10:00 PM - 6:00 AM)
        $nightDiffPay = round($hourlyRate * 0.10 * $nightDiffHours, 2);

        return [
            'overtime_pay' => $totalOtPay,
            'night_diff_pay' => $nightDiffPay,
            'total_overtime' => round($totalOtPay + $nightDiffPay, 2),
        ];
    }
}
