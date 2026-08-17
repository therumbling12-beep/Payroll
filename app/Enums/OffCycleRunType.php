<?php

declare(strict_types=1);

namespace App\Enums;

enum OffCycleRunType: string
{
    case FINAL_PAY = 'final_pay';
    case SPECIAL_BONUS = 'special_bonus';
    case SALARY_DIFFERENTIAL = 'salary_differential';
    case THIRTEENTH_MONTH_ADVANCE = 'thirteenth_month_advance';

    public function label(): string
    {
        return match ($this) {
            self::FINAL_PAY => 'Final Pay & Separation Settlement',
            self::SPECIAL_BONUS => 'Special Performance Bonus',
            self::SALARY_DIFFERENTIAL => 'Salary Differential / Retroactive Pay',
            self::THIRTEENTH_MONTH_ADVANCE => '13th Month Pay Advance',
        };
    }
}
