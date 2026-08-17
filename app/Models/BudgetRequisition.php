<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetRequisition extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'released' => 'bg-blue-50 text-blue-700 border-blue-200',
            'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-amber-50 text-amber-700 border-amber-200',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'approved' => 'Approved by Finance',
            'released' => 'Fund Released',
            'rejected' => 'Rejected',
            default => 'Awaiting Finance Approval',
        };
    }
}
