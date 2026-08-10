<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayrollBatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThirteenthMonthBatch extends Model
{
    protected $fillable = [
        'year',
        'status',
        'total_amount',
        'submitted_to_admin_at',
        'approved_by_admin_at',
        'budget_requested_at',
        'budget_received_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayrollBatchStatus::class,
            'total_amount' => 'decimal:2',
            'submitted_to_admin_at' => 'datetime',
            'approved_by_admin_at' => 'datetime',
            'budget_requested_at' => 'datetime',
            'budget_received_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function computations(): HasMany
    {
        return $this->hasMany(ThirteenthMonthComputation::class, 'year', 'year');
    }
}
