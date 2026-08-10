<?php

declare(strict_types=1);

namespace App\Enums;

enum PayrollBatchStatus: string
{
    case DRAFT = 'draft';
    case PENDING_ADMIN = 'pending_admin';
    case APPROVED = 'approved';
    case BUDGET_REQUESTED = 'budget_requested';
    case BUDGET_RECEIVED = 'budget_received';
    case RELEASED = 'released';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft / Unsubmitted',
            self::PENDING_ADMIN => 'Pending Admin Approval',
            self::APPROVED => 'Approved by Admin',
            self::BUDGET_REQUESTED => 'Budget Requested from Finance',
            self::BUDGET_RECEIVED => 'Funds Received from Finance',
            self::RELEASED => 'Released / Paid Out',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-gray-100 text-gray-700 border-gray-200',
            self::PENDING_ADMIN => 'bg-amber-50 text-amber-700 border-amber-200',
            self::APPROVED => 'bg-blue-50 text-blue-700 border-blue-200',
            self::BUDGET_REQUESTED => 'bg-purple-50 text-purple-700 border-purple-200',
            self::BUDGET_RECEIVED => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::RELEASED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        };
    }
}
