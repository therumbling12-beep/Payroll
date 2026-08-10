<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiComplianceLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'flagged_issues' => 'array',
        'resolution_suggestions' => 'array',
    ];

    public function salaryComputation(): BelongsTo
    {
        return $this->belongsTo(SalaryComputation::class);
    }
}
