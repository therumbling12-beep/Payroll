<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected $appends = [
        'step_salary',
        'salary_amount',
    ];

    /**
     * Get the salary grade that owns this step.
     */
    public function salaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class);
    }

    /**
     * Get the calculated or explicit step salary amount.
     */
    protected function stepSalary(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if ($this->base_amount !== null && (float) $this->base_amount > 0) {
                    return (float) $this->base_amount;
                }

                $gradeMin = (float) ($this->salaryGrade?->min_salary ?? 0.0);
                $pct = (float) ($this->increment_percentage ?? 0.0);

                return round($gradeMin * (1 + ($pct / 100)), 2);
            }
        );
    }

    /**
     * Alias for stepSalary to support legacy references.
     */
    protected function salaryAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->step_salary
        );
    }
}
