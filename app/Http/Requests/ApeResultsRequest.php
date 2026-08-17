<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApeResultsRequest extends FormRequest
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
            'attendance_status' => ['required', 'string', 'in:attended,scheduled,no_show,waived,rescheduled'],
            'medical_clearance_status' => ['required', 'string', 'in:fit_to_work,fit_with_restrictions,temporarily_unfit,pending_results'],
            'findings_summary' => ['nullable', 'string', 'max:1000'],
            'medical_certificate' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
