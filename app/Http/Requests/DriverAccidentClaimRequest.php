<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverAccidentClaimRequest extends FormRequest
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
            'incident_type' => ['required', 'string', 'in:Work Injury,Accident - Hospitalization,Accident - Medical Bills,Emergency Assistance,Death Benefit'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'bill_amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:1000'],
            'vehicle_plate_number' => ['nullable', 'string', 'max:50'],
            'trip_id' => ['nullable', 'string', 'max:100'],
            'diagnosis' => ['nullable', 'string', 'max:500'],
            'police_report' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'medical_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'incident_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
