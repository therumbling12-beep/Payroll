<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovernmentContributionTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_type',
        'effective_year',
        'bracket_from',
        'bracket_to',
        'monthly_salary_credit',
        'employee_rate',
        'employer_rate',
        'employee_fixed_amount',
        'employer_fixed_amount',
        'ec_contribution',
        'base_tax',
        'excess_rate',
    ];

    protected function casts(): array
    {
        return [
            'effective_year' => 'integer',
            'bracket_from' => 'decimal:2',
            'bracket_to' => 'decimal:2',
            'monthly_salary_credit' => 'decimal:2',
            'employee_rate' => 'decimal:4',
            'employer_rate' => 'decimal:4',
            'employee_fixed_amount' => 'decimal:2',
            'employer_fixed_amount' => 'decimal:2',
            'ec_contribution' => 'decimal:2',
            'base_tax' => 'decimal:2',
            'excess_rate' => 'decimal:4',
        ];
    }
}
