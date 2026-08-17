<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HmoConfigurationRequest extends FormRequest
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
            'hmo_has_provider' => ['nullable', 'string', 'in:0,1'],
            'hmo_provider_name' => ['required', 'string', 'max:255'],
            'hmo_plan_type' => ['required', 'string', 'in:Room & Board,Outpatient,Comprehensive'],
            'hmo_premium_shoulder_type' => ['required', 'string', 'in:company,shared'],
            'hmo_company_share_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'hmo_employee_share_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'hmo_coverage_start_months' => ['required', 'integer', 'in:0,3,6,12'],
            'hmo_dependent_coverage' => ['nullable', 'string', 'in:0,1'],
            'hmo_max_dependents' => ['required', 'integer', 'min:0', 'max:10'],
            'hmo_base_employee_premium' => ['required', 'numeric', 'min:0'],
            'hmo_base_dependent_premium' => ['required', 'numeric', 'min:0'],
        ];
    }
}
