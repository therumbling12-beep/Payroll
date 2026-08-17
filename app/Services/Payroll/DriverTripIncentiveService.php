<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\CompanySetting;
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

        $tier3Quota = (int) CompanySetting::getValue('driver_tier3_trip_quota', 70);
        $tier3Bonus = (float) CompanySetting::getValue('driver_tier3_bonus', 3000.00);

        $tier2Quota = (int) CompanySetting::getValue('driver_tier2_trip_quota', 50);
        $tier2Bonus = (float) CompanySetting::getValue('driver_tier2_bonus', 1500.00);

        $tier1Quota = (int) CompanySetting::getValue('driver_tier1_trip_quota', 30);
        $tier1Bonus = (float) CompanySetting::getValue('driver_tier1_bonus', 500.00);

        if ($tripsCount >= $tier3Quota) {
            $amount = $tier3Bonus;
            $tier = "Tier 3 ({$tier3Quota}+ Trips Quota Bonus)";
        } elseif ($tripsCount >= $tier2Quota) {
            $amount = $tier2Bonus;
            $tier = "Tier 2 ({$tier2Quota}-" . ($tier3Quota - 1) . " Trips Quota Bonus)";
        } elseif ($tripsCount >= $tier1Quota) {
            $amount = $tier1Bonus;
            $tier = "Tier 1 ({$tier1Quota}-" . ($tier2Quota - 1) . " Trips Quota Bonus)";
        } else {
            $amount = 0.00;
            $tier = "Below Quota Threshold (<{$tier1Quota} Trips)";
        }

        return [
            'incentive_amount' => $amount,
            'tier_label' => $tier,
            'trips_count' => $tripsCount,
        ];
    }
}
