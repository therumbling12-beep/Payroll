<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\MinimumWageOrder;
use Illuminate\Support\Collection;

class MinimumWageGuardService
{
    /**
     * Evaluate employee compensation against statutory Regional Wage Orders (NCR-24).
     *
     * @param Collection<int, Employee>|null $employees
     * @return array{
     *     is_fully_compliant: bool,
     *     compliant_count: int,
     *     non_compliant_count: int,
     *     statutory_daily_rate: float,
     *     region_code: string,
     *     wage_order_no: string,
     *     evaluations: array<int, array<string, mixed>>
     * }
     */
    public function evaluateCompliance(?Collection $employees = null): array
    {
        $employees = $employees ?? Employee::with('department')->get();

        $defaultStatutoryDaily = (float) CompanySetting::getValue('statutory_minimum_wage_daily_rate', 645.00);
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $driverDailyFloor = (float) CompanySetting::getValue('driver_default_daily_floor', 750.00);

        $wageOrder = MinimumWageOrder::active()->where('region_code', 'NCR')->first();
        $statutoryDailyRate = $wageOrder ? (float) ($wageOrder->daily_rate ?? $defaultStatutoryDaily) : $defaultStatutoryDaily;
        $wageOrderNo = $wageOrder ? $wageOrder->wage_order_number : 'NCR-24';
        $regionCode = $wageOrder ? $wageOrder->region_code : 'NCR';

        $evaluations = [];
        $compliantCount = 0;
        $nonCompliantCount = 0;

        foreach ($employees as $employee) {
            $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
            
            if ($employee->daily_rate > 0) {
                $effectiveDailyRate = (float) $employee->daily_rate;
            } elseif ($employee->monthly_rate > 0) {
                $effectiveDailyRate = round((float) $employee->monthly_rate / $workingDays, 2);
            } else {
                $effectiveDailyRate = $isDriver ? $driverDailyFloor : 0.00; // Estimated driver floor
            }

            $variance = round($effectiveDailyRate - $statutoryDailyRate, 2);
            $isCompliant = $effectiveDailyRate >= $statutoryDailyRate;

            if ($isCompliant) {
                $compliantCount++;
            } else {
                $nonCompliantCount++;
            }

            $evaluations[] = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => "{$employee->first_name} {$employee->last_name}",
                'position' => $employee->position,
                'department' => $employee->department?->name ?? 'General',
                'employment_status' => $employee->employment_status,
                'is_driver' => $isDriver,
                'monthly_rate' => (float) $employee->monthly_rate,
                'effective_daily_rate' => $effectiveDailyRate,
                'statutory_daily_rate' => $statutoryDailyRate,
                'variance' => $variance,
                'is_compliant' => $isCompliant,
                'status_label' => $isCompliant ? 'COMPLIANT' : 'NON-COMPLIANT',
            ];
        }

        return [
            'is_fully_compliant' => $nonCompliantCount === 0,
            'compliant_count' => $compliantCount,
            'non_compliant_count' => $nonCompliantCount,
            'statutory_daily_rate' => $statutoryDailyRate,
            'region_code' => $regionCode,
            'wage_order_no' => $wageOrderNo,
            'evaluations' => $evaluations,
        ];
    }
}
