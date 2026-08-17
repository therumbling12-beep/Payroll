<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaimCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('tax_classification') || empty($this->input('tax_classification'))) {
            $this->merge(['tax_classification' => 'non_taxable']);
        }

        if (! $this->has('type') && $this->route('category')) {
            $this->merge(['type' => $this->route('category')->type]);
        }

        if (! $this->has('code') && $this->route('category')) {
            $this->merge(['code' => $this->route('category')->code]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->input('category_id');
        $isUpdate = (bool) $this->route('category');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                'max:50',
                Rule::unique('claim_categories', 'code')->ignore($categoryId),
            ],
            'type' => [$isUpdate ? 'nullable' : 'required', 'string', Rule::in(['reimbursement', 'incentive', 'maternity'])],
            'tax_classification' => ['nullable', 'string', Rule::in(['non_taxable', 'de_minimis', 'taxable'])],
            'color_tag' => ['nullable', 'string', 'max:30'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'de_minimis_annual_cap' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'requires_receipt' => ['nullable', 'boolean'],
            'applicable_to' => ['required', 'string', Rule::in(['all', 'driver', 'regular', 'staff'])],
            'spending_limit_period' => ['nullable', 'string', Rule::in(['per_claim', 'per_month', 'per_year'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
