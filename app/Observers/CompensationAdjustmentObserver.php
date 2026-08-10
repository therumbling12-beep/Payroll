<?php

namespace App\Observers;

use App\Models\CompensationAdjustment;
use App\Models\PerformanceBonus;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CompensationAdjustmentObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the CompensationAdjustment "updated" event.
     */
    public function updated(CompensationAdjustment $adjustment): void
    {
        // Only trigger sync if the adjustment was just approved
        if ($adjustment->wasChanged('status') && $adjustment->status === 'approved') {
            $employee = $adjustment->employee;

            if ($employee) {
                // 1. Sync Salary Increase to Employee model
                if ($adjustment->new_rate && $adjustment->new_rate > 0) {
                    $isDriver = str_contains($employee->position, 'Driver');
                    if ($isDriver) {
                        $employee->daily_rate = $adjustment->new_rate;
                    } else {
                        $employee->monthly_rate = $adjustment->new_rate;
                    }
                }

                // 2. Sync Promotion Title
                if (!empty($adjustment->new_position)) {
                    $employee->position = $adjustment->new_position;
                }

                $employee->save();

                // 3. Sync One-Time Bonus to PerformanceBonus table for immediate payroll pickup
                if ($adjustment->bonus_amount && $adjustment->bonus_amount > 0) {
                    PerformanceBonus::create([
                        'employee_id' => $employee->id,
                        'cutoff_period' => '2026-07-01_15',
                        'bonus_amount' => $adjustment->bonus_amount,
                        'reason' => 'Merit Promotion Bonus: ' . ($adjustment->reason ?? 'Approved Merit Increase'),
                    ]);
                }
            }
        }
    }
}
