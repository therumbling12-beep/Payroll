<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupLifePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'policy_number',
        'provider_name',
        'coverage_type',
        'sum_assured',
        'monthly_premium',
        'company_shoulder_pct',
        'beneficiary_primary_name',
        'beneficiary_primary_relation',
        'beneficiary_secondary_name',
        'beneficiary_secondary_relation',
        'policy_start_date',
        'policy_end_date',
        'status',
    ];

    protected $casts = [
        'sum_assured' => 'decimal:2',
        'monthly_premium' => 'decimal:2',
        'company_shoulder_pct' => 'decimal:2',
        'policy_start_date' => 'date',
        'policy_end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get CSS badge classes for policy status
     */
    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'claimed' => 'bg-blue-50 text-blue-700 border-blue-200',
            'lapsed' => 'bg-amber-50 text-amber-700 border-amber-200',
            'terminated' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-gray-50 text-gray-700 border-gray-200',
        };
    }
}
