<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'cutoff_period',
        'days_worked',
        'regular_hours',
        'overtime_hours',
        'night_diff_hours',
        'rest_day_hours',
        'holiday_regular_hours',
        'holiday_special_hours',
        'tardiness_minutes',
        'undertime_minutes',
        'lates_count',
    ];

    protected function casts(): array
    {
        return [
            'days_worked' => 'integer',
            'regular_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'night_diff_hours' => 'decimal:2',
            'rest_day_hours' => 'decimal:2',
            'holiday_regular_hours' => 'decimal:2',
            'holiday_special_hours' => 'decimal:2',
            'tardiness_minutes' => 'integer',
            'undertime_minutes' => 'integer',
            'lates_count' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
