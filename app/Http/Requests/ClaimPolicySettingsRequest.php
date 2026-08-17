<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimPolicySettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Fuel Policy
            'fuel_default_pump_price' => ['required', 'numeric', 'min:1'],
            'fuel_default_efficiency_kpl' => ['required', 'numeric', 'min:1'],
            'fuel_tolerance_percentage' => ['required', 'numeric', 'min:1', 'max:100'],

            // Performance Appraisal
            'performance_bonus_multiplier' => ['required', 'numeric', 'min:1'],

            // Driver Incentives
            'driver_consistency_bonus' => ['required', 'numeric', 'min:0'],
            'driver_attendance_bonus' => ['required', 'numeric', 'min:0'],
            'milestone_tiers' => ['nullable', 'array'],
            'milestone_tiers.*.tier' => ['required', 'integer', 'min:1'],
            'milestone_tiers.*.min_rides' => ['required', 'integer', 'min:1'],
            'milestone_tiers.*.amount' => ['required', 'numeric', 'min:1'],
            'milestone_tiers.*.label' => ['nullable', 'string', 'max:100'],

            // Statutory Ceilings
            'sss_max_msc' => ['required', 'numeric', 'min:1000'],
            'medical_de_minimis_annual_cap' => ['required', 'numeric', 'min:0'],
        ];
    }
}
