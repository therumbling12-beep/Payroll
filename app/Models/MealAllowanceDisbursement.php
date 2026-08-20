<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealAllowanceDisbursement extends Model
{
    use HasFactory;

    protected $table = 'meal_allowance_disbursements';

    protected $fillable = [
        'employee_id',
        'cutoff_period',
        'days_rendered',
        'daily_subsidy_rate',
        'gross_amount',
        'tax_exempt_amount',
        'taxable_excess_amount',
        'status',
        'disbursed_at',
        'notes',
    ];

    protected $casts = [
        'days_rendered' => 'integer',
        'daily_subsidy_rate' => 'float',
        'gross_amount' => 'float',
        'tax_exempt_amount' => 'float',
        'taxable_excess_amount' => 'float',
        'disbursed_at' => 'datetime',
    ];

    /**
     * Relationship to Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope for a specific payroll cutoff period
     */
    public function scopeForCutoff($query, string $cutoff)
    {
        return $query->where('cutoff_period', $cutoff);
    }

    /**
     * Scope for pending review disbursements
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for HR approved disbursements
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for released disbursements
     */
    public function scopeReleased($query)
    {
        return $query->where('status', 'released_to_payroll');
    }
}
