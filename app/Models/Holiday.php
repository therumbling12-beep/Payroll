<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'holiday_date',
        'holiday_type',
        'proclamation_number',
        'year',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date:Y-m-d',
            'year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeInPeriod(Builder $query, string $startDate, string $endDate): void
    {
        $query->whereBetween('holiday_date', [$startDate, $endDate]);
    }

    public function scopeRegular(Builder $query): void
    {
        $query->where('holiday_type', 'regular');
    }

    public function scopeSpecial(Builder $query): void
    {
        $query->where('holiday_type', 'special_non_working');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
