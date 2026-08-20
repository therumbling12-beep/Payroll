<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualPayrollRequest extends FormRequest
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
            'cutoff_period' => ['required', 'string'],
            'base_pay' => ['required', 'numeric', 'min:0'],
            'trip_earnings' => ['nullable', 'numeric', 'min:0'],
            'performance_bonus' => ['nullable', 'numeric', 'min:0'],
            'reimbursements' => ['nullable', 'numeric', 'min:0'],
            'sss_deduction' => ['required', 'numeric', 'min:0'],
            'philhealth_deduction' => ['required', 'numeric', 'min:0'],
            'pagibig_deduction' => ['required', 'numeric', 'min:0'],
            'withholding_tax' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:pending_approval,approved_legal,released_financial,rejected'],
        ];
    }
}
