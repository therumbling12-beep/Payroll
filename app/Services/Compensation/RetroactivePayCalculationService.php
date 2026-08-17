<?php

declare(strict_types=1);

namespace App\Services\Compensation;

use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;
use Illuminate\Support\Facades\DB;

class RetroactivePayCalculationService
{
    /**
     * Compute Retroactive Pay Differential based on Daily Rates ($/26) and Days Rendered (known.md §6.8)
     *
     * Formula:
     * - Old Daily Rate = Old Monthly / 26
     * - New Daily Rate = New Monthly / 26
     * - Daily Differential = New Daily Rate - Old Daily Rate
     * - Retroactive Pay = Daily Differential x Days Rendered Before Cutoff
     *
     * @return array<string, mixed>
     */
    public function calculateRetroactiveDifferential(
        Employee $employee,
        float $newMonthlyRate,
        string $effectiveDate,
        int $daysWorked = 13
    ): array {
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $driverDefault = (float) CompanySetting::getValue('driver_default_baseline_salary', 28000.00);
        $staffDefault = (float) CompanySetting::getValue('staff_default_baseline_salary', 25000.00);

        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
        $oldMonthlyRate = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * $workingDays : ($isDriver ? $driverDefault : $staffDefault)));

        $oldDailyRate = round($oldMonthlyRate / $workingDays, 2);
        $newDailyRate = round($newMonthlyRate / $workingDays, 2);
        $dailyDifferential = round($newDailyRate - $oldDailyRate, 2);

        $days = max(1, min(60, $daysWorked));
        $retroactivePay = round($dailyDifferential * $days, 2);

        return [
            'employee_id' => $employee->id,
            'employee_name' => "{$employee->first_name} {$employee->last_name}",
            'employee_code' => $employee->employee_code,
            'position' => $employee->position,
            'effective_date' => $effectiveDate,
            'days_worked_prior' => $days,
            'old_monthly_rate' => $oldMonthlyRate,
            'old_daily_rate' => $oldDailyRate,
            'new_monthly_rate' => $newMonthlyRate,
            'new_daily_rate' => $newDailyRate,
            'daily_differential' => $dailyDifferential,
            'retroactive_pay' => $retroactivePay,
            'formula' => "(New Daily PHP " . number_format($newDailyRate, 2) . " - Old Daily PHP " . number_format($oldDailyRate, 2) . ") x {$days} Days = PHP " . number_format($retroactivePay, 2),
        ];
    }

    /**
     * Seamlessly Inject Approved Retroactive Pay into Active Payroll Salary Computation
     */
    public function injectRetroactivePayToPayroll(Employee $employee, float $retroPay, string $cutoffPeriod): bool
    {
        return DB::transaction(function () use ($employee, $retroPay, $cutoffPeriod) {
            $computation = SalaryComputation::where('employee_id', $employee->id)
                ->where('cutoff_period', $cutoffPeriod)
                ->first();

            if ($computation) {
                $oldReimbursements = (float) ($computation->reimbursements ?? 0.0);
                $oldGross = (float) $computation->gross_pay;
                $oldNet = (float) $computation->net_pay;

                $computation->update([
                    'reimbursements' => round($oldReimbursements + $retroPay, 2),
                    'gross_pay' => round($oldGross + $retroPay, 2),
                    'net_pay' => round($oldNet + $retroPay, 2),
                ]);

                PayrollAuditTrail::create([
                    'action' => 'RETROACTIVE_PAY_INJECTED',
                    'model_type' => SalaryComputation::class,
                    'model_id' => $computation->id,
                    'user_name' => 'System / Compensation Engine',
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'old_values' => ['gross_pay' => $oldGross, 'net_pay' => $oldNet],
                    'new_values' => ['gross_pay' => $oldGross + $retroPay, 'net_pay' => $oldNet + $retroPay, 'retroactive_pay' => $retroPay],
                ]);

                return true;
            }

            return false;
        });
    }
}
