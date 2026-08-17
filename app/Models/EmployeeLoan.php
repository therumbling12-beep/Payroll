<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'loan_type',
        'reference_no',
        'principal_amount',
        'total_amount_due',
        'term_months',
        'semi_monthly_amortization',
        'total_paid',
        'remaining_balance',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'total_amount_due' => 'decimal:2',
            'semi_monthly_amortization' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'term_months' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function amortizationLogs(): HasMany
    {
        return $this->hasMany(LoanAmortizationLog::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('loan_type', $type);
    }

    public function getLoanTypeLabelAttribute(): string
    {
        return match ($this->loan_type) {
            'sss_salary_loan' => 'SSS Salary Loan',
            'sss_calamity_loan' => 'SSS Calamity Loan',
            'hdmf_multi_purpose_loan' => 'Pag-IBIG Multi-Purpose Loan',
            'hdmf_housing_loan' => 'Pag-IBIG Housing Loan',
            'company_emergency_loan' => 'Company Emergency Cash Advance',
            default => ucwords(str_replace('_', ' ', $this->loan_type)),
        };
    }
}
