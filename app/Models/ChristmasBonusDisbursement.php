<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChristmasBonusDisbursement extends Model
{
    use HasFactory;

    protected $table = 'christmas_bonus_disbursements';

    protected $fillable = [
        'employee_id',
        'bonus_year',
        'base_bonus_amount',
        'months_tenure',
        'is_prorated',
        'calculated_bonus_amount',
        'status',
        'released_at',
        'notes',
    ];

    protected $casts = [
        'bonus_year' => 'integer',
        'base_bonus_amount' => 'float',
        'months_tenure' => 'float',
        'is_prorated' => 'boolean',
        'calculated_bonus_amount' => 'float',
        'released_at' => 'datetime',
    ];

    /**
     * Relationship to Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope for a specific calendar year
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('bonus_year', $year);
    }

    /**
     * Scope for pending review
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for HR approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'hr_approved');
    }

    /**
     * Scope for released to payroll
     */
    public function scopeReleased($query)
    {
        return $query->where('status', 'released_to_payroll');
    }
}
