<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_grade_id',
        'step_number',
        'years_required',
        'increment_percentage',
        'base_amount',
    ];

    protected $casts = [
        'step_number' => 'integer',
        'years_required' => 'float',
        'increment_percentage' => 'float',
        'base_amount' => 'float',
    ];

    /**
     * Get the salary grade that owns this step.
     */
    public function salaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class);
    }
}
