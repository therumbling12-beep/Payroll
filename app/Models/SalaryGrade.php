<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_code',
        'job_level',
        'position_name',
        'sample_positions',
        'min_salary',
        'max_salary',
        'annual_growth_rate',
        'effectivity_date',
    ];

    protected $casts = [
        'min_salary' => 'float',
        'max_salary' => 'float',
        'annual_growth_rate' => 'float',
        'effectivity_date' => 'date',
    ];

    /**
     * Get Midpoint (50th Percentile) of the salary grade band.
     */
    public function getMidpointAttribute(): float
    {
        return round(($this->min_salary + $this->max_salary) / 2, 2);
    }

    /**
     * Get Total Spread Amount of the salary grade band.
     */
    public function getSpreadAttribute(): float
    {
        return round($this->max_salary - $this->min_salary, 2);
    }

    /**
     * Get Spread Percentage.
     */
    public function getSpreadPercentageAttribute(): float
    {
        return $this->min_salary > 0 ? round((($this->max_salary - $this->min_salary) / $this->min_salary) * 100, 1) : 0.0;
    }

    /**
     * Calculate Specific Percentile Amount within the band (0.0 to 1.0).
     */
    public function getPercentile(float $percentile): float
    {
        $percentile = max(0.0, min(1.0, $percentile));
        return round($this->min_salary + ($percentile * ($this->max_salary - $this->min_salary)), 2);
    }

    /**
     * Check if Grade minimum meets DOLE Minimum Wage (NCR-27 755/day = ~19,630/mo).
     */
    public function isMinimumWageCompliant(float $floor = 19630.00): bool
    {
        return $this->min_salary >= $floor;
    }

    /**
     * Get all employees associated with this salary grade's position.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'position', 'position_name');
    }

    /**
     * Get all tenure steps configured for this salary grade.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(SalaryStep::class)->orderBy('step_number');
    }
}
