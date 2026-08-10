<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;

class SalaryComputationObserver
{
    /**
     * Handle the SalaryComputation "created" event.
     */
    public function created(SalaryComputation $computation): void
    {
        PayrollAuditTrail::create([
            'user_name' => auth()->user()?->name ?? 'HR System Administrator',
            'action' => 'CREATED',
            'model_type' => 'SalaryComputation',
            'model_id' => $computation->id,
            'old_values' => null,
            'new_values' => $computation->toArray(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }

    /**
     * Handle the SalaryComputation "updated" event.
     */
    public function updated(SalaryComputation $computation): void
    {
        PayrollAuditTrail::create([
            'user_name' => auth()->user()?->name ?? 'HR System Administrator',
            'action' => 'UPDATED',
            'model_type' => 'SalaryComputation',
            'model_id' => $computation->id,
            'old_values' => $computation->getOriginal(),
            'new_values' => $computation->getChanges(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}
