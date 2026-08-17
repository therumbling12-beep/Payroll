<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OffCycleRunType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OffCyclePayroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_number',
        'run_type',
        'title',
        'payout_date',
        'status',
        'total_gross',
        'total_deductions',
        'total_net_pay',
        'notes',
        'approved_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'run_type' => OffCycleRunType::class,
            'payout_date' => 'date',
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net_pay' => 'decimal:2',
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OffCyclePayrollItem::class, 'off_cycle_payroll_id');
    }
}
