<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverPoolConfigRequest extends FormRequest
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
            'contribution_rate' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'company_match_pct' => ['nullable', 'numeric', 'min:0', 'max:200'],
        ];
    }
}
