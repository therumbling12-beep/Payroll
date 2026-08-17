<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CompensationAdjustment;
use App\Models\PayrollAuditTrail;
use App\Models\PerformanceBonus;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CompensationAdjustmentObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the CompensationAdjustment "created" event.
     */
    public function created(CompensationAdjustment $adjustment): void
    {
        PayrollAuditTrail::create([
            'user_name' => 'HR Compensation Specialist',
            'action' => 'COMPENSATION_PROPOSAL_CREATED',
            'model_type' => 'CompensationAdjustment',
            'model_id' => $adjustment->id,
            'new_values' => [
                'type' => $adjustment->type,
                'subject_name' => $adjustment->display_name,
                'target_position' => $adjustment->new_position ?? $adjustment->old_position,
                'target_rate' => $adjustment->new_rate,
                'status' => $adjustment->status,
                'reason' => $adjustment->reason,
            ],
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }

    /**
     * Handle the CompensationAdjustment "updated" event.
     */
    public function updated(CompensationAdjustment $adjustment): void
    {
        // 1. Audit Trail Logging for status transitions or rate edits
        if ($adjustment->wasChanged('status') || $adjustment->wasChanged('new_rate')) {
            PayrollAuditTrail::create([
                'user_name' => 'HR / Administrator',
                'action' => 'COMPENSATION_' . strtoupper($adjustment->status),
                'model_type' => 'CompensationAdjustment',
                'model_id' => $adjustment->id,
                'old_values' => $adjustment->getOriginal(),
                'new_values' => $adjustment->getChanges(),
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);
        }

        // 2. Trigger sync to Employee & PerformanceBonus if approved
        if ($adjustment->wasChanged('status') && $adjustment->status === 'approved') {
            $employee = $adjustment->employee;

            if ($employee) {
                // Sync Salary Increase to Employee model
                if ($adjustment->new_rate && $adjustment->new_rate > 0) {
                    $isDriver = str_contains($employee->position, 'Driver');
                    if ($isDriver) {
                        $employee->daily_rate = round($adjustment->new_rate / 26, 2);
                        $employee->monthly_rate = $adjustment->new_rate;
                    } else {
                        $employee->monthly_rate = $adjustment->new_rate;
                    }
                }

                // Sync Promotion Title
                if (!empty($adjustment->new_position)) {
                    $employee->position = $adjustment->new_position;
                }

                $employee->save();

                // Sync One-Time Bonus to PerformanceBonus table for immediate payroll pickup
                if ($adjustment->bonus_amount && $adjustment->bonus_amount > 0) {
                    $currentCutoff = now()->day <= 15 ? now()->format('Y-m-01_15') : now()->format('Y-m-16_31');

                    PerformanceBonus::create([
                        'employee_id' => $employee->id,
                        'cutoff_period' => $currentCutoff,
                        'bonus_amount' => $adjustment->bonus_amount,
                        'reason' => 'Merit Promotion Bonus: ' . ($adjustment->reason ?? 'Approved Merit Increase'),
                    ]);
                }
            }
        }
    }
}

