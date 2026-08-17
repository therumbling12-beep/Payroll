<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CompensationAdjustmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([CompensationAdjustmentObserver::class])]
class CompensationAdjustment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'old_rate' => 'float',
        'new_rate' => 'float',
        'monthly_ctc' => 'float',
        'annual_ctc' => 'float',
        'thirteenth_month_liability' => 'float',
        'employer_statutory_total' => 'float',
        'bonus_amount' => 'float',
        'signing_bonus' => 'float',
        'allowance_amount' => 'float',
        'transport_allowance' => 'float',
        'meal_allowance' => 'float',
        'comms_allowance' => 'float',
        'competitor_offer' => 'float',
        'peer_median_salary' => 'float',
        'wage_distortion_variance_pct' => 'float',
        'urgency_days' => 'integer',
        'effective_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isApplicant(): bool
    {
        return $this->subject_type === 'applicant';
    }

    public function isModeA(): bool
    {
        return ($this->mode ?? 'mode_a') === 'mode_a';
    }

    public function isModeB(): bool
    {
        return ($this->mode ?? 'mode_a') === 'mode_b';
    }

    public function isWageDistorted(): bool
    {
        return in_array($this->internal_equity_status, ['WAGE_DISTORTION_WARNING', 'EXCEEDS_BAND_MAXIMUM'], true);
    }

    public function isBudgetApproved(): bool
    {
        return $this->budget_impact_status === 'BUDGET_APPROVED';
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->isApplicant()) {
            return $this->applicant_name ?? 'External Applicant';
        }

        return trim(($this->employee?->first_name ?? '').' '.($this->employee?->last_name ?? ''));
    }

    public function getDisplayPositionAttribute(): string
    {
        if ($this->isApplicant()) {
            return $this->applicant_position ?? 'Applicant';
        }

        return $this->employee?->position ?? ($this->old_position ?? 'Staff');
    }

    public function getUrgencyLevelAttribute(): string
    {
        $days = (int) ($this->urgency_days ?? 7);
        if ($days <= 2) {
            return 'critical';
        }
        if ($days <= 5) {
            return 'soon';
        }

        return 'open';
    }
}
