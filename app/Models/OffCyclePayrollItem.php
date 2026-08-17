<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffCyclePayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'off_cycle_payroll_id',
        'employee_id',
        'basic_pay_earned',
        'pro_rated_13th_month',
        'leave_conversion_pay',
        'bonuses_differentials',
        'reimbursements',
        'gross_amount',
        'withholding_tax',
        'loan_deduction',
        'other_deductions',
        'total_deductions',
        'net_settlement_pay',
        'computation_breakdown',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'basic_pay_earned' => 'decimal:2',
            'pro_rated_13th_month' => 'decimal:2',
            'leave_conversion_pay' => 'decimal:2',
            'bonuses_differentials' => 'decimal:2',
            'reimbursements' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'withholding_tax' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_settlement_pay' => 'decimal:2',
            'computation_breakdown' => 'array',
        ];
    }

    public function offCyclePayroll(): BelongsTo
    {
        return $this->belongsTo(OffCyclePayroll::class, 'off_cycle_payroll_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
