<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EssEmergencyLoaRequest extends FormRequest
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
            'employee_id' => ['required', 'exists:employees,id'],
            'patient_type' => ['required', 'string', 'in:employee,dependent'],
            'dependent_id' => ['nullable', 'exists:hmo_dependents,id'],
            'hospital_name' => ['required', 'string', 'max:150'],
            'attending_physician' => ['nullable', 'string', 'max:150'],
            'diagnosis' => ['required', 'string', 'max:500'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'doctor_order_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
