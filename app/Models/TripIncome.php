<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripIncome extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'cutoff_period',
        'total_trips',
        'total_trip_earnings',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
