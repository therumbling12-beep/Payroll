<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HmoEnrollmentApplicationRequest extends FormRequest
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
            'hmo_provider' => ['nullable', 'string', 'max:255'],
            'coverage_tier' => ['required', 'string', 'in:Bronze,Silver,Gold,Platinum,Basic,Plus,Premium,Driver Fleet Care'],
            'annual_limit' => ['nullable', 'numeric', 'min:0'],
            'coverage_start_date' => ['nullable', 'date'],
            'coverage_end_date' => ['nullable', 'date', 'after_or_equal:coverage_start_date'],
            'id_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'marriage_cert' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'dependents' => ['nullable', 'array'],
            'dependents.*.full_name' => ['required_with:dependents', 'string', 'max:255'],
            'dependents.*.relationship' => ['required_with:dependents', 'string', 'in:Spouse,Child,Parent'],
            'dependents.*.birth_date' => ['nullable', 'date'],
            'dependents.*.gender' => ['nullable', 'string', 'in:Male,Female'],
            'dependents.*.birth_cert' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
