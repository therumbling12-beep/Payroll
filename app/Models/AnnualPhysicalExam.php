<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualPhysicalExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'exam_year',
        'schedule_date',
        'time_slot',
        'facility_name',
        'package_type',
        'attendance_status',
        'medical_clearance_status',
        'findings_summary',
        'medical_certificate_path',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'exam_year' => 'integer',
        'schedule_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get CSS badge classes for attendance status
     */
    public function attendanceBadgeClasses(): string
    {
        return match ($this->attendance_status) {
            'attended' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'scheduled' => 'bg-blue-50 text-blue-700 border-blue-200',
            'rescheduled' => 'bg-amber-50 text-amber-700 border-amber-200',
            'no_show' => 'bg-rose-50 text-rose-700 border-rose-200',
            'waived' => 'bg-gray-100 text-gray-700 border-gray-200',
            default => 'bg-gray-50 text-gray-700 border-gray-200',
        };
    }

    /**
     * Get CSS badge classes for medical clearance
     */
    public function clearanceBadgeClasses(): string
    {
        return match ($this->medical_clearance_status) {
            'fit_to_work' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'fit_with_restrictions' => 'bg-amber-50 text-amber-700 border-amber-200',
            'temporarily_unfit' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }

    /**
     * Human-friendly label for medical clearance
     */
    public function clearanceLabel(): string
    {
        return match ($this->medical_clearance_status) {
            'fit_to_work' => 'Fit to Work (Cleared)',
            'fit_with_restrictions' => 'Fit with Work Restrictions',
            'temporarily_unfit' => 'Temporarily Unfit (Re-eval Required)',
            default => 'Pending Medical Results',
        };
    }
}
