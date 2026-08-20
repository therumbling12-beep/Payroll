<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\SalaryComputationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([SalaryComputationObserver::class])]
class SalaryComputation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'cutoff_period',
        'base_pay',
        'trip_earnings',
        'driver_trip_incentive',
        'holiday_pay',
        'overtime_pay',
        'night_diff_pay',
        'performance_bonus',
        'reimbursements',
        'gross_pay',
        'sss_deduction',
        'sss_employer',
        'philhealth_deduction',
        'philhealth_employer',
        'pagibig_deduction',
        'pagibig_employer',
        'ec_contribution',
        'platform_fee_deduction',
        'loan_deduction',
        'withholding_tax',
        'tardiness_deduction',
        'undertime_deduction',
        'total_deductions',
        'net_pay',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'base_pay' => 'decimal:2',
            'trip_earnings' => 'decimal:2',
            'driver_trip_incentive' => 'decimal:2',
            'holiday_pay' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'night_diff_pay' => 'decimal:2',
            'performance_bonus' => 'decimal:2',
            'reimbursements' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'sss_deduction' => 'decimal:2',
            'sss_employer' => 'decimal:2',
            'philhealth_deduction' => 'decimal:2',
            'philhealth_employer' => 'decimal:2',
            'pagibig_deduction' => 'decimal:2',
            'pagibig_employer' => 'decimal:2',
            'ec_contribution' => 'decimal:2',
            'platform_fee_deduction' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'withholding_tax' => 'decimal:2',
            'tardiness_deduction' => 'decimal:2',
            'undertime_deduction' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function aiComplianceLog(): HasOne
    {
        return $this->hasOne(AiComplianceLog::class);
    }

    public function loanAmortizationLogs(): HasMany
    {
        return $this->hasMany(LoanAmortizationLog::class);
    }

    /**
     * Transparent Accessor for Computation Formula breakdown
     */
    protected function computationBreakdown(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'formula_str' => "Base Pay (PHP {$this->base_pay}) + Trips (PHP {$this->trip_earnings}) + Incentives (PHP {$this->driver_trip_incentive}) + Holiday (PHP {$this->holiday_pay}) + OT (PHP {$this->overtime_pay}) + NSD (PHP {$this->night_diff_pay}) + Bonus (PHP {$this->performance_bonus}) = PHP {$this->gross_pay} Gross",
                'deduction_str' => "SSS (PHP {$this->sss_deduction}) + PhilHealth (PHP {$this->philhealth_deduction}) + PagIBIG (PHP {$this->pagibig_deduction}) + Loans (PHP {$this->loan_deduction}) + Late/Undertime (PHP " . number_format((float)$this->tardiness_deduction + (float)$this->undertime_deduction, 2) . ") + BIR Tax (PHP {$this->withholding_tax}) = PHP {$this->total_deductions} Deductions",
                'net_str' => "Gross (PHP {$this->gross_pay}) - Deductions (PHP {$this->total_deductions}) = PHP {$this->net_pay} Net",
            ]
        );
    }

    public function scopeStatus(Builder $query, ?string $status): void
    {
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
    }
}
