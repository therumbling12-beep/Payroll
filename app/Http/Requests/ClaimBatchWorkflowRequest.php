<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimBatchWorkflowRequest extends FormRequest
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
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['required', 'exists:claims,id'],
            'action' => ['required', 'string', 'in:batch_approve_supervisor,batch_approve_hr,batch_approve_finance,batch_approve_admin,batch_queue_payroll,batch_reject'],
            'role' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
