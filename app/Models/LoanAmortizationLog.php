<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanAmortizationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_loan_id',
        'salary_computation_id',
        'cutoff_period',
        'amount_deducted',
        'remaining_balance_after',
        'deducted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_deducted' => 'decimal:2',
            'remaining_balance_after' => 'decimal:2',
            'deducted_at' => 'datetime',
        ];
    }

    public function employeeLoan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class);
    }

    public function salaryComputation(): BelongsTo
    {
        return $this->belongsTo(SalaryComputation::class);
    }
}
