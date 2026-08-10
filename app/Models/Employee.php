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
        'daily_rate',
        'monthly_rate',
        'payment_mode',
        'bank_account_no',
        'payment_method',
        'bank_name',
        'bank_account_number',
    ];

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
}
