<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccreditedFacility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'facility_type',
        'region',
        'address',
        'contact_number',
        'is_emergency_ready',
        'is_active',
    ];

    protected $casts = [
        'is_emergency_ready' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope search by name, address, or region
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('address', 'like', "%{$term}%")
                ->orWhere('region', 'like', "%{$term}%")
                ->orWhere('facility_type', 'like', "%{$term}%");
        });
    }
}
