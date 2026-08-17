<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupLifeEnrollmentRequest extends FormRequest
{
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
            'employee_id' => ['required', 'exists:employees,id'],
            'provider_name' => ['required', 'string', 'max:255'],
            'coverage_type' => ['required', 'string', 'in:Group Term Life,Accidental Death & Dismemberment,Total & Permanent Disability,Critical Illness Rider'],
            'sum_assured' => ['required', 'numeric', 'min:50000'],
            'monthly_premium' => ['required', 'numeric', 'min:0'],
            'company_shoulder_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'beneficiary_primary_name' => ['required', 'string', 'max:255'],
            'beneficiary_primary_relation' => ['required', 'string', 'max:100'],
            'beneficiary_secondary_name' => ['nullable', 'string', 'max:255'],
            'beneficiary_secondary_relation' => ['nullable', 'string', 'max:100'],
            'policy_start_date' => ['required', 'date'],
            'policy_end_date' => ['required', 'date', 'after_or_equal:policy_start_date'],
        ];
    }
}
