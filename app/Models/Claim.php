<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $attributes = [
        'disbursement_method' => 'cash',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'non_taxable_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'distance_traveled_km' => 'decimal:2',
        'vehicle_fuel_efficiency_kpl' => 'decimal:2',
        'fuel_liters' => 'decimal:2',
        'fuel_pump_price' => 'decimal:2',
        'expected_fuel_cost' => 'decimal:2',
        'fuel_variance_pct' => 'decimal:2',
        'auto_validated' => 'boolean',
        'odometer_start' => 'decimal:2',
        'odometer_end' => 'decimal:2',
        'performance_rating' => 'decimal:2',
        'sss_maternity_share' => 'decimal:2',
        'company_maternity_topup' => 'decimal:2',
        'maternity_leave_days' => 'integer',
        'sss_reimbursement_date' => 'date',
        'effective_date' => 'date',
        'expense_date' => 'date',
        'supervisor_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'finance_approved_at' => 'datetime',
        'payroll_queued_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
        'cash_released_at' => 'datetime',
        'is_duplicate_flagged' => 'boolean',
        'duplicate_risk_score' => 'integer',
        'duplicate_match_details' => 'array',
    ];

    public function isCashSettlement(): bool
    {
        return ($this->disbursement_method ?? 'cash') === 'cash';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function categoryModel(): BelongsTo
    {
        return $this->belongsTo(ClaimCategory::class, 'category_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->approval_status ?? $this->status) {
            'draft' => 'Draft',
            'pending_hr', 'pending' => 'Pending HR Validation',
            'pending_admin' => 'Pending Admin Review',
            'pending_finance' => 'Pending Finance Approval',
            'approved' => 'Approved (Ready to Queue)',
            'payroll_queued' => 'Queued for Payroll',
            'paid' => 'Included in Payroll / Paid',
            'rejected' => 'Rejected',
            'revision_required' => 'Revision Required',
            default => ucfirst((string) ($this->approval_status ?? $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->approval_status ?? $this->status) {
            'draft' => 'bg-gray-100 text-gray-700 border-gray-200',
            'pending_hr', 'pending' => 'bg-amber-50 text-amber-800 border-amber-200',
            'pending_admin' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            'pending_finance' => 'bg-purple-50 text-purple-800 border-purple-200',
            'approved' => 'bg-blue-50 text-blue-800 border-blue-200',
            'payroll_queued' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'paid' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            'rejected' => 'bg-rose-50 text-rose-800 border-rose-200',
            'revision_required' => 'bg-orange-50 text-orange-800 border-orange-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
    }

    /**
     * Get multi-step approval timeline data for slide-out drawer
     */
    public function getTimelineAttribute(): array
    {
        $status = $this->approval_status ?? $this->status;
        $isRejected = $status === 'rejected';
        $isRevision = $status === 'revision_required';

        return [
            [
                'step' => 1,
                'title' => 'Submission & Validation',
                'actor' => 'Employee / Fleet Ops',
                'status' => 'completed',
                'date' => $this->created_at?->format('M d, Y h:i A') ?? '—',
                'remarks' => $this->description,
            ],
            [
                'step' => 2,
                'title' => 'HR Policy Validation',
                'actor' => 'HR Department',
                'status' => $this->hr_approved_at ? 'completed' : ($status === 'pending_hr' || $status === 'pending' ? 'active' : ($isRejected || $isRevision ? 'flagged' : 'pending')),
                'date' => $this->hr_approved_at?->format('M d, Y h:i A') ?? 'Pending',
                'remarks' => $this->hr_remarks ?? ($status === 'pending_hr' ? 'Awaiting HR validation against category guidelines.' : null),
            ],
            [
                'step' => 3,
                'title' => 'Administrator Authorization',
                'actor' => 'Executive Admin',
                'status' => $this->admin_approved_at ? 'completed' : ($status === 'pending_admin' ? 'active' : ($this->hr_approved_at ? 'pending' : 'waiting')),
                'date' => $this->admin_approved_at?->format('M d, Y h:i A') ?? ($status === 'pending_admin' ? 'Pending Admin Action' : '—'),
                'remarks' => $this->admin_remarks,
            ],
            [
                'step' => 4,
                'title' => 'Financial Budget Approval',
                'actor' => 'Financial Management (Team 5)',
                'status' => $this->finance_approved_at ? 'completed' : ($status === 'pending_finance' ? 'active' : ($this->admin_approved_at ? 'pending' : 'waiting')),
                'date' => $this->finance_approved_at?->format('M d, Y h:i A') ?? ($status === 'pending_finance' ? 'Pending Budget Verification' : '—'),
                'remarks' => $this->finance_remarks,
            ],
            [
                'step' => 5,
                'title' => 'Payroll Queue & Release',
                'actor' => 'Payroll Engine (Team 4)',
                'status' => in_array($status, ['approved', 'payroll_queued', 'paid'], true) ? 'completed' : 'waiting',
                'date' => $this->payroll_queued_at?->format('M d, Y h:i A') ?? (in_array($status, ['approved', 'payroll_queued', 'paid'], true) ? 'Queued for Cutoff ' . $this->cutoff_period : '—'),
                'remarks' => in_array($status, ['approved', 'payroll_queued', 'paid'], true) ? 'Synced to employee cutoff pay run.' : null,
            ],
        ];
    }

    /**
     * Get number of days the claim has been waiting since submission
     */
    public function waitingDays(): int
    {
        if (! $this->created_at) {
            return 0;
        }

        $createdAt = $this->created_at instanceof \Carbon\CarbonInterface
            ? $this->created_at
            : \Illuminate\Support\Carbon::parse($this->created_at);

        return (int) max(0, $createdAt->diffInDays(now()));
    }

    /**
     * Check if the pending claim has exceeded the 3-day SLA turnaround
     */
    public function isOverdue(): bool
    {
        $status = $this->approval_status ?? $this->status;
        $isPending = in_array($status, ['pending_hr', 'pending_admin', 'pending_finance', 'pending'], true);

        return $isPending && $this->waitingDays() >= 3;
    }

    /**
     * Plain-language waiting turnaround label
     */
    public function getWaitingLabelAttribute(): string
    {
        $days = $this->waitingDays();
        if ($days === 0) {
            return 'Submitted Today';
        }

        return "Waiting {$days} " . ($days === 1 ? 'Day' : 'Days');
    }

    /**
     * Scope: Claims requiring action by HR, Admin, or Finance
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopeNeedsAction($query)
    {
        return $query->whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending']);
    }

    /**
     * Scope: Claims awaiting HR validation
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopePendingHr($query)
    {
        return $query->whereIn('approval_status', ['pending_hr', 'pending']);
    }

    /**
     * Scope: Claims awaiting Admin authorization
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopePendingAdmin($query)
    {
        return $query->where('approval_status', 'pending_admin');
    }

    /**
     * Scope: Claims awaiting Finance budget approval
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopePendingFinance($query)
    {
        return $query->where('approval_status', 'pending_finance');
    }

    /**
     * Scope: Claims fully authorized and approved
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope: Claims queued into active payroll cutoff
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopePayrollQueued($query)
    {
        return $query->where('approval_status', 'payroll_queued');
    }

    /**
     * Scope: Claims disbursed/paid
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopePaid($query)
    {
        return $query->where('approval_status', 'paid');
    }

    /**
     * Scope: Rejected claims
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    /**
     * Scope: Claims ready for payroll inclusion (approved or already queued)
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopeReadyForPayroll($query)
    {
        return $query->whereIn('approval_status', ['approved', 'payroll_queued']);
    }

    /**
     * Scope: Pending claims exceeding 3-day SLA
     *
     * @param \Illuminate\Database\Eloquent\Builder<Claim> $query
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending'])
            ->where('created_at', '<=', now()->subDays(3));
    }
}
