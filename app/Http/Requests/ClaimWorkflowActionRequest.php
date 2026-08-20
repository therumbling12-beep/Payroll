<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimWorkflowActionRequest extends FormRequest
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
            'action' => ['required', 'string', 'in:approve_supervisor,approve_hr,approve_finance,approve_admin,reject,queue_payroll,mark_paid,release_cash'],
            'approved_amount' => ['nullable', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
            'role' => ['nullable', 'string', 'max:100'],
        ];
    }
}
