<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\TripIncome;

class DriverTripIncentiveService
{
    /**
     * Compute multi-tier trip volume incentives based on known.md Section 2.5.
     *
     * @return array{incentive_amount: float, tier_label: string, trips_count: int}
     */
    public function compute(Employee $employee, string $cutoffPeriod): array
    {
        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
        if (! $isDriver) {
            return [
                'incentive_amount' => 0.00,
                'tier_label' => 'Not Applicable',
                'trips_count' => 0,
            ];
        }

        $tripIncome = TripIncome::where('employee_id', $employee->id)
            ->where('cutoff_period', $cutoffPeriod)
            ->first();

        $tripsCount = $tripIncome ? (int) $tripIncome->total_trips : 0;

        if ($tripsCount >= 70) {
            $amount = 3000.00;
            $tier = 'Tier 3 (70+ Trips Quota Bonus)';
        } elseif ($tripsCount >= 50) {
            $amount = 1500.00;
            $tier = 'Tier 2 (50-69 Trips Quota Bonus)';
        } elseif ($tripsCount >= 30) {
            $amount = 500.00;
            $tier = 'Tier 1 (30-49 Trips Quota Bonus)';
        } else {
            $amount = 0.00;
            $tier = 'Below Quota Threshold (<30 Trips)';
        }

        return [
            'incentive_amount' => $amount,
            'tier_label' => $tier,
            'trips_count' => $tripsCount,
        ];
    }
}
