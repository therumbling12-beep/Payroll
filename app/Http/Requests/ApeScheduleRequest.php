<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApeScheduleRequest extends FormRequest
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
            'exam_year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'schedule_date' => ['required', 'date'],
            'time_slot' => ['required', 'string', 'max:100'],
            'facility_name' => ['required', 'string', 'max:255'],
            'package_type' => ['required', 'string', 'in:Standard Occupational,Executive Comprehensive,Driver Road Fit'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
