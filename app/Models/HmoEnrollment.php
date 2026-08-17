<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HmoEnrollment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'coverage_start_date' => 'date',
        'coverage_end_date' => 'date',
        'hr_reviewed_at' => 'datetime',
        'renewed_at' => 'datetime',
        'mbl_amount' => 'decimal:2',
        'annual_limit' => 'decimal:2',
        'monthly_premium' => 'decimal:2',
        'dependent_count' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function utilizationLogs(): HasMany
    {
        return $this->hasMany(HmoUtilizationLog::class);
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(HmoDependent::class, 'hmo_enrollment_id');
    }

    public function budgetRequisition(): BelongsTo
    {
        return $this->belongsTo(BudgetRequisition::class, 'budget_requisition_id');
    }

    /**
     * Check if coverage expires within 30 days
     */
    public function isExpiringSoon(): bool
    {
        if (! $this->coverage_end_date) {
            return false;
        }

        $now = Carbon::now();
        $days = (int) $now->diffInDays($this->coverage_end_date, false);

        return $days >= 0 && $days <= 30;
    }

    /**
     * Calculate remaining days until coverage expiration
     */
    public function daysUntilExpiry(): ?int
    {
        if (! $this->coverage_end_date) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->coverage_end_date, false);
    }

    /**
     * Get total utilized amount from logs
     */
    public function totalUtilized(): float
    {
        return (float) $this->utilizationLogs()->sum('utilized_amount');
    }

    /**
     * Get remaining balance for this HMO enrollment
     */
    public function remainingBalance(): float
    {
        $limit = (float) ($this->annual_limit ?: $this->mbl_amount);

        return max(0.00, $limit - $this->totalUtilized());
    }

    /**
     * Check if remaining MBL is below 20% of annual limit
     */
    public function isLowBalance(): bool
    {
        $limit = (float) ($this->annual_limit ?: $this->mbl_amount);
        if ($limit <= 0.0) {
            return false;
        }

        return $this->remainingBalance() < ($limit * 0.20);
    }

    /**
     * Get percentage of annual MBL utilized
     */
    public function utilizationPercentage(): float
    {
        $limit = (float) ($this->annual_limit ?: $this->mbl_amount);
        if ($limit <= 0.0) {
            return 0.0;
        }

        return min(100.0, round(($this->totalUtilized() / $limit) * 100.0, 1));
    }

    /**
     * Calculate company vs employee co-share amounts for this enrollment
     *
     * @return array{company_share: float, employee_share: float, company_share_pct: float, employee_share_pct: float}
     */
    public function calculateCoShare(): array
    {
        $companySharePct = (float) (CompanySetting::getValue('hmo_company_share_pct') ?? 80.0);
        $employeeSharePct = (float) (CompanySetting::getValue('hmo_employee_share_pct') ?? (100.0 - $companySharePct));

        $totalPremium = (float) ($this->monthly_premium ?? 0.0);
        $companyShare = round(($totalPremium * $companySharePct) / 100.0, 2);
        $employeeShare = max(0.0, round($totalPremium - $companyShare, 2));

        return [
            'company_share' => $companyShare,
            'employee_share' => $employeeShare,
            'company_share_pct' => $companySharePct,
            'employee_share_pct' => $employeeSharePct,
        ];
    }

    /**
     * Alias for daysUntilExpiry
     */
    public function daysUntilExpiration(): ?int
    {
        return $this->daysUntilExpiry();
    }

    /**
     * Lifecycle stage helper methods
     */
    public function isSubmitted(): bool
    {
        return $this->enrollment_status === 'submitted';
    }

    public function isHrApproved(): bool
    {
        return $this->enrollment_status === 'hr_approved';
    }

    public function isBudgetRequested(): bool
    {
        return $this->enrollment_status === 'budget_requested';
    }

    public function isActive(): bool
    {
        return $this->enrollment_status === 'active' || $this->status === 'active';
    }

    public function isRejected(): bool
    {
        return $this->enrollment_status === 'rejected';
    }
}
