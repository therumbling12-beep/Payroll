<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccidentClaim extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'incident_date' => 'date',
        'bill_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'documents_uploaded' => 'boolean',
        'hr_reviewed_at' => 'datetime',
        'admin_reviewed_at' => 'datetime',
        'finance_reviewed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get CSS badge classes for the current workflow status
     */
    public function statusBadgeClasses(): string
    {
        return match ($this->workflow_status) {
            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'returned' => 'bg-rose-50 text-rose-700 border-rose-200',
            'pending_hr' => 'bg-amber-50 text-amber-700 border-amber-200',
            'pending_admin' => 'bg-blue-50 text-blue-700 border-blue-200',
            'pending_finance' => 'bg-purple-50 text-purple-700 border-purple-200',
            default => 'bg-gray-50 text-gray-700 border-gray-200',
        };
    }

    /**
     * Get human-readable label for the current status
     */
    public function statusLabel(): string
    {
        return match ($this->workflow_status) {
            'approved' => 'Approved & Disbursed',
            'returned' => 'Returned for Revision',
            'pending_hr' => 'Awaiting HR Review',
            'pending_admin' => 'Awaiting Admin Review',
            'pending_finance' => 'Awaiting Finance Release',
            default => ucfirst(str_replace('_', ' ', $this->workflow_status ?? 'Pending')),
        };
    }

    /**
     * Identify current pending step
     */
    public function currentStep(): string
    {
        if ($this->workflow_status === 'approved') return 'Completed';
        if ($this->workflow_status === 'returned') return 'Returned';
        if ($this->hr_status === 'pending') return 'HR Validation';
        if ($this->admin_status === 'pending') return 'Admin Approval';
        if ($this->finance_status === 'pending') return 'Finance Verification';
        return 'Completed';
    }
}
