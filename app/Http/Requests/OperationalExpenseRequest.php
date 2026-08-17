<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OperationalExpenseRequest extends FormRequest
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
            'category_id' => ['required', 'exists:claim_categories,id'],
            'expense_subtype' => ['nullable', 'string', 'in:fuel,toll,maintenance,parking,meal,communication,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'receipt_number' => ['required', 'string', 'max:100'],
            'merchant_name' => ['required', 'string', 'max:150'],
            'merchant_tin' => ['nullable', 'string', 'max:50'],
            'expense_date' => ['required', 'date'],
            'cutoff_period' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:1000'],
            'receipt_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
