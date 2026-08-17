<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EssClaimSubmissionRequest extends FormRequest
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
            'category_id' => ['nullable', 'exists:claim_categories,id'],
            'type' => ['required', 'string', 'in:expense,medical,maternity,accident,incentive,performance'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'receipt_number' => ['required', 'string', 'max:100'],
            'merchant_name' => ['nullable', 'string', 'max:150'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'distance_traveled_km' => ['nullable', 'numeric', 'min:0'],
            'fuel_pump_price' => ['nullable', 'numeric', 'min:0'],
            'vehicle_fuel_efficiency_kpl' => ['nullable', 'numeric', 'min:0'],
            'receipt_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'maternity_type' => ['nullable', 'string', 'in:normal_caesarean,solo_parent,miscarriage_emergency'],
            'physician_license_no' => ['nullable', 'string', 'max:50'],
            'incident_date' => ['nullable', 'date'],
            'incident_location' => ['nullable', 'string', 'max:255'],
            'hospital_name' => ['nullable', 'string', 'max:150'],
            'police_report_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
