<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_name',
        'min_salary',
        'max_salary',
        'annual_growth_rate',
    ];

    protected $casts = [
        'min_salary' => 'float',
        'max_salary' => 'float',
        'annual_growth_rate' => 'float',
    ];
}
