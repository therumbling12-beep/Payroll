<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClaimCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'tax_classification',
        'color_tag',
        'max_amount',
        'de_minimis_annual_cap',
        'is_active',
        'requires_receipt',
        'applicable_to',
        'spending_limit_period',
        'description',
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'de_minimis_annual_cap' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_receipt' => 'boolean',
    ];

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'category_id');
    }

    public function isDeMinimis(): bool
    {
        return $this->tax_classification === 'de_minimis';
    }

    public function isNonTaxable(): bool
    {
        return $this->tax_classification === 'non_taxable';
    }

    public function isTaxable(): bool
    {
        return $this->tax_classification === 'taxable';
    }

    public function getTaxLabel(): string
    {
        return match ($this->tax_classification) {
            'de_minimis' => 'De Minimis (Capped)',
            'taxable' => 'Taxable Compensation',
            default => 'Non-Taxable Reimbursement',
        };
    }

    public function getTaxBadgeClasses(): string
    {
        return match ($this->tax_classification) {
            'de_minimis' => 'bg-cyan-50 text-cyan-800 border-cyan-200',
            'taxable' => 'bg-rose-50 text-rose-800 border-rose-200',
            default => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        };
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->color_tag) {
            'emerald' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'sky', 'blue' => 'bg-sky-50 text-sky-800 border-sky-200',
            'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
            'violet', 'purple' => 'bg-purple-50 text-purple-800 border-purple-200',
            'pink' => 'bg-pink-50 text-pink-800 border-pink-200',
            'rose' => 'bg-rose-50 text-rose-800 border-rose-200',
            'indigo' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            'teal' => 'bg-teal-50 text-teal-800 border-teal-200',
            default => 'bg-gray-50 text-gray-800 border-gray-200',
        };
    }
}
