<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FuelReimbursementRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'distance_traveled_km' => ['required', 'numeric', 'min:0.1'],
            'vehicle_fuel_efficiency_kpl' => ['nullable', 'numeric', 'min:1'],
            'fuel_liters' => ['nullable', 'numeric', 'min:0.1'],
            'fuel_pump_price' => ['nullable', 'numeric', 'min:0.1'],
            'receipt_number' => ['required', 'string', 'max:100'],
            'merchant_name' => ['required', 'string', 'max:150'],
            'merchant_tin' => ['nullable', 'string', 'max:50'],
            'expense_date' => ['required', 'date'],
            'cutoff_period' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'receipt_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
