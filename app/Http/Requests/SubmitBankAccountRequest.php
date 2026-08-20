<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitBankAccountRequest extends FormRequest
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
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'regex:/^[0-9\- ]{10,25}$/'],
            'proof_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * Custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee identity is required.',
            'bank_name.required' => 'Bank name is required.',
            'account_number.required' => 'Please enter your Security Bank account number.',
            'account_number.regex' => 'Please enter a valid Security Bank account number (10 to 20 digits).',
            'proof_document.mimes' => 'Proof attachment must be a JPG, PNG, WEBP, or PDF file.',
            'proof_document.max' => 'Proof attachment must not exceed 5MB.',
        ];
    }
}
