<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BenefitType extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'min_tenure_months' => 'integer',
    ];

    public function categoryBadgeClasses(): string
    {
        return match ($this->category) {
            'Health Insurance' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Insurance' => 'bg-blue-50 text-blue-700 border-blue-200',
            'Government Mandated' => 'bg-amber-50 text-amber-700 border-amber-200',
            'Statutory' => 'bg-purple-50 text-purple-700 border-purple-200',
            default => 'bg-gray-50 text-gray-700 border-gray-200',
        };
    }
}
