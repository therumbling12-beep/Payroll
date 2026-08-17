<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverPoolLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'entry_type',
        'amount',
        'running_balance',
        'reference_code',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    /**
     * Parent Employee (if driver-specific contribution)
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Human-friendly label for entry type
     */
    public function getEntryTypeLabelAttribute(): string
    {
        return match ($this->entry_type) {
            'driver_contribution' => 'Driver 3% Deduction',
            'company_subsidy_match' => 'TripWise Company Match',
            'claim_disbursement' => 'Accident Claim Payout',
            'adjustment' => 'Fund Adjustment',
            default => ucfirst(str_replace('_', ' ', (string) $this->entry_type)),
        };
    }
}
