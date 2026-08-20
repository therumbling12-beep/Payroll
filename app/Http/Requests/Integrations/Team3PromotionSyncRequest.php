<?php

declare(strict_types=1);

namespace App\Http\Requests\Integrations;

use Illuminate\Foundation\Http\FormRequest;

class Team3PromotionSyncRequest extends FormRequest
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
            'employee_code' => 'required|string|exists:employees,employee_code',
            'promoted_position' => 'required|string|max:100',
            'target_grade_code' => 'nullable|string|exists:salary_grades,grade_code',
            'promotion_order_number' => 'required|string|max:50',
            'effective_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
