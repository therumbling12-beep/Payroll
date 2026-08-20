<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceIncentiveLeave extends Model
{
    use HasFactory;

    protected $table = 'service_incentive_leaves';

    protected $fillable = [
        'employee_id',
        'year',
        'entitled_days',
        'used_days',
        'cash_converted_days',
        'cash_converted_amount',
        'status',
        'notes',
        'leave_logs',
    ];

    protected $casts = [
        'year' => 'integer',
        'entitled_days' => 'integer',
        'used_days' => 'integer',
        'cash_converted_days' => 'integer',
        'cash_converted_amount' => 'float',
        'leave_logs' => 'array',
    ];

    /**
     * Relationship to Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Compute remaining leave balance available
     */
    public function getRemainingDaysAttribute(): int
    {
        return max(0, $this->entitled_days - $this->used_days - $this->cash_converted_days);
    }

    /**
     * Scope for a specific calendar year
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
