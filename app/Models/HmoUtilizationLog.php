<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmoUtilizationLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'utilized_at' => 'date',
        'utilized_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function hmoEnrollment(): BelongsTo
    {
        return $this->belongsTo(HmoEnrollment::class);
    }
}
