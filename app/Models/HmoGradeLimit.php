<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HmoGradeLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_min',
        'grade_max',
        'title',
        'mbl_amount',
        'room_and_board',
        'max_dependents',
        'dependent_premium_coshare_pct',
        'is_active',
    ];

    protected $casts = [
        'grade_min' => 'integer',
        'grade_max' => 'integer',
        'mbl_amount' => 'decimal:2',
        'max_dependents' => 'integer',
        'dependent_premium_coshare_pct' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array and JSON form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'grade_label',
        'roles_description',
        'room_label',
        'tier_name',
        'room_board_type',
        'benefits_description',
    ];

    /**
     * Human-friendly formatted pay grade range label
     */
    public function getGradeLabelAttribute(): string
    {
        if ($this->grade_min === $this->grade_max) {
            return "Pay Grade {$this->grade_min}";
        }

        return "Pay Grade {$this->grade_min} – {$this->grade_max}";
    }

    /**
     * Plain-English job level / roles context description
     */
    public function getRolesDescriptionAttribute(): string
    {
        if ($this->grade_min === $this->grade_max) {
            return "Salary Grade {$this->grade_min} positions";
        }

        return "Salary Grade {$this->grade_min} to {$this->grade_max} positions";
    }

    /**
     * Human-friendly room and board label
     */
    public function getRoomLabelAttribute(): string
    {
        return match ($this->room_and_board) {
            'semi_private' => 'Semi-Private Room',
            'private' => 'Private Room',
            'suite' => 'Executive Suite',
            default => ucfirst(str_replace('_', ' ', (string) $this->room_and_board)),
        };
    }

    /**
     * Room and board type alias for room_label
     */
    public function getRoomBoardTypeAttribute(): string
    {
        return $this->room_label;
    }

    /**
     * Plan / Tier Name alias for title
     */
    public function getTierNameAttribute(): string
    {
        return (string) $this->title;
    }

    /**
     * Human-friendly core benefit and dependent coverage summary
     */
    public function getBenefitsDescriptionAttribute(): string
    {
        $depText = $this->max_dependents > 0
            ? "Up to {$this->max_dependents} Dependents (" . number_format((float) $this->dependent_premium_coshare_pct, 0) . "% co-pay)"
            : 'Principal Employee Only';

        return "Annual MBL PHP " . number_format((float) $this->mbl_amount, 2) . " • {$depText}";
    }
}
