<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CompensationAdjustment;
use App\Models\PayrollAuditTrail;
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
        if ($adjustment->wasChanged('status') || $adjustment->wasChanged('new_rate')) {
            PayrollAuditTrail::create([
                'user_name' => 'HR / Administrator',
                'action' => 'COMPENSATION_' . strtoupper((string) $adjustment->status),
                'model_type' => 'CompensationAdjustment',
                'model_id' => $adjustment->id,
                'old_values' => $adjustment->getOriginal(),
                'new_values' => $adjustment->getChanges(),
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);
        }
    }
}
