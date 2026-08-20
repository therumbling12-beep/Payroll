<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentChannelRequest extends FormRequest
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
            'payment_mode' => ['required', 'string', 'in:bank,cash'],
            'bank_name' => ['nullable', 'required_if:payment_mode,bank', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'required_if:payment_mode,bank', 'string', 'regex:/^[0-9\- ]{10,25}$/'],
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
            'payment_mode.required' => 'Please select a disbursement channel.',
            'payment_mode.in' => 'Invalid payment channel selected.',
            'bank_name.required_if' => 'Bank name is required for bank transfer disbursements.',
            'bank_account_number.required_if' => 'Security Bank account number is required for bank transfer disbursements.',
            'bank_account_number.regex' => 'Please enter a valid Security Bank account number (10-20 digits).',
        ];
    }
}
