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

#[ObservedBy([SalaryComputationObserver::class])]
class SalaryComputation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'cutoff_period',
        'base_pay',
        'trip_earnings',
        'performance_bonus',
        'reimbursements',
        'gross_pay',
        'sss_deduction',
        'philhealth_deduction',
        'pagibig_deduction',
        'hmo_insurance_deduction',
        'platform_fee_deduction',
        'withholding_tax',
        'total_deductions',
        'net_pay',
        'status',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function aiComplianceLog()
    {
        return $this->hasOne(AiComplianceLog::class);
    }

    /**
     * Transparent Accessor for Computation Formula breakdown
     */
    protected function computationBreakdown(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'formula_str' => "Base Pay (₱{$this->base_pay}) + Trips (₱{$this->trip_earnings}) + Bonus (₱{$this->performance_bonus}) = ₱{$this->gross_pay} Gross",
                'deduction_str' => "SSS (₱{$this->sss_deduction}) + PhilHealth (₱{$this->philhealth_deduction}) + PagIBIG (₱{$this->pagibig_deduction}) + HMO (₱{$this->hmo_insurance_deduction}) + BIR Tax (₱{$this->withholding_tax}) = ₱{$this->total_deductions} Deductions",
                'net_str' => "Gross (₱{$this->gross_pay}) - Deductions (₱{$this->total_deductions}) + Reimbursements (₱{$this->reimbursements}) = ₱{$this->net_pay} Net",
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
