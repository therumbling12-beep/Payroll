<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchPayrollUpdateRequest extends FormRequest
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
            'cutoff_period' => ['required', 'string', 'max:50'],
            'computations' => ['required', 'array', 'min:1'],
            'computations.*.id' => ['required', 'exists:salary_computations,id'],
            'computations.*.base_pay' => ['required', 'numeric', 'min:0'],
            'computations.*.trip_earnings' => ['nullable', 'numeric', 'min:0'],
            'computations.*.driver_trip_incentive' => ['nullable', 'numeric', 'min:0'],
            'computations.*.overtime_pay' => ['nullable', 'numeric', 'min:0'],
            'computations.*.holiday_pay' => ['nullable', 'numeric', 'min:0'],
            'computations.*.night_diff_pay' => ['nullable', 'numeric', 'min:0'],
            'computations.*.sss_deduction' => ['required', 'numeric', 'min:0'],
            'computations.*.philhealth_deduction' => ['required', 'numeric', 'min:0'],
            'computations.*.pagibig_deduction' => ['required', 'numeric', 'min:0'],
            'computations.*.loan_deduction' => ['nullable', 'numeric', 'min:0'],
            'computations.*.tardiness_deduction' => ['nullable', 'numeric', 'min:0'],
            'computations.*.undertime_deduction' => ['nullable', 'numeric', 'min:0'],
            'computations.*.reimbursements' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
