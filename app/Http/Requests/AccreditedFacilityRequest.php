<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccreditedFacilityRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'facility_type' => ['required', 'string', 'in:Hospital,Clinic,Diagnostic Center,Emergency'],
            'region' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'contact_number' => ['nullable', 'string', 'max:100'],
            'is_emergency_ready' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
