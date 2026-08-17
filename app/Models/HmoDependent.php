<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmoDependent extends Model
{
    use HasFactory;

    protected $fillable = [
        'hmo_enrollment_id',
        'employee_id',
        'full_name',
        'relationship',
        'birth_date',
        'gender',
        'birth_cert_path',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Parent HMO Enrollment
     */
    public function hmoEnrollment(): BelongsTo
    {
        return $this->belongsTo(HmoEnrollment::class, 'hmo_enrollment_id');
    }

    /**
     * Alias for hmoEnrollment
     */
    public function enrollment(): BelongsTo
    {
        return $this->hmoEnrollment();
    }

    /**
     * Parent Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
