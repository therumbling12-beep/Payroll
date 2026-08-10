<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThirteenthMonthComputation extends Model
{
    protected $fillable = [
        'employee_id',
        'year',
        'monthly_salary',
        'months_worked',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'amount' => 'decimal:2',
            'months_worked' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
