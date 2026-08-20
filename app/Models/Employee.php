<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'position',
        'hire_date',
        'regularization_date',
        'employment_status',
        'performance_rating',
        'daily_rate',
        'monthly_rate',
        'pagibig_voluntary_contribution',
        'current_step',
        'step_status',
        'step_hold_reason',
        'payment_mode',
        'bank_account_no',
        'payment_method',
        'bank_name',
        'bank_account_number',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'regularization_date' => 'date',
        'daily_rate' => 'float',
        'monthly_rate' => 'float',
        'pagibig_voluntary_contribution' => 'decimal:2',
        'current_step' => 'integer',
    ];

    /**
     * Calculate tenure (years of service) from regularization or hire date.
     */
    public function getYearsOfServiceAttribute(): float
    {
        $startDate = $this->regularization_date ?? $this->hire_date;
        if (! $startDate) {
            return 0.0;
        }

        $days = (float) now()->diffInDays($startDate, false);
        if ($days > 0) {
            return 0.0;
        }

        return round(abs($days) / 365.25, 1);
    }

    /**
     * Get tenure summary string (e.g., "3 yrs, 4 mos").
     */
    public function getTenureTextAttribute(): string
    {
        $startDate = $this->regularization_date ?? $this->hire_date;
        if (! $startDate) {
            return '0 months';
        }

        $diff = now()->diff($startDate);
        $years = $diff->y;
        $months = $diff->m;

        if ($years > 0) {
            return "{$years} yrs, {$months} mos";
        }

        return "{$months} mos";
    }

    /**
     * Days remaining until standard 6-month probationary end date.
     */
    public function getProbationaryDaysRemainingAttribute(): ?int
    {
        if ($this->employment_status !== 'probationary' || ! $this->hire_date) {
            return null;
        }

        $targetDate = $this->hire_date->copy()->addMonths(6);

        return (int) now()->diffInDays($targetDate, false);
    }


    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaryComputations(): HasMany
    {
        return $this->hasMany(SalaryComputation::class);
    }

    public function compensationAdjustments(): HasMany
    {
        return $this->hasMany(CompensationAdjustment::class);
    }

    public function tripIncomes(): HasMany
    {
        return $this->hasMany(TripIncome::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function performanceBonuses(): HasMany
    {
        return $this->hasMany(PerformanceBonus::class);
    }

    public function accidentClaims(): HasMany
    {
        return $this->hasMany(AccidentClaim::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function serviceIncentiveLeaves(): HasMany
    {
        return $this->hasMany(ServiceIncentiveLeave::class);
    }

    public function mealAllowanceDisbursements(): HasMany
    {
        return $this->hasMany(MealAllowanceDisbursement::class);
    }

    public function christmasBonusDisbursements(): HasMany
    {
        return $this->hasMany(ChristmasBonusDisbursement::class);
    }

    public function bankAccountSubmissions(): HasMany
    {
        return $this->hasMany(BankAccountSubmission::class);
    }

    public function latestBankSubmission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BankAccountSubmission::class)->latestOfMany();
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term) {
            $query->where(function (Builder $q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                  ->orWhere('last_name', 'like', "%{$term}%")
                  ->orWhere('employee_code', 'like', "%{$term}%");
            });
        }
    }

    public function scopeDepartment(Builder $query, ?string $deptId): void
    {
        if ($deptId && $deptId !== 'all') {
            $query->where('department_id', $deptId);
        }
    }

    /**
     * Check if the employee is a driver or fleet crew member
     */
    public function isDriver(): bool
    {
        $pos = strtolower((string) $this->position);
        $dept = strtolower((string) ($this->department?->name ?? ''));

        return str_contains($pos, 'driver')
            || str_contains($pos, 'chauffeur')
            || str_contains($dept, 'fleet');
    }

    /**
     * Scope a query to only include drivers or fleet crew members
     */
    public function scopeDrivers(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where('position', 'like', '%driver%')
              ->orWhere('position', 'like', '%chauffeur%')
              ->orWhereHas('department', fn (Builder $d) => $d->where('name', 'like', '%fleet%'));
        });
    }
}
