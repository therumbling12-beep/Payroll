<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryComputationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => new EmployeeResource($this->employee),
            'cutoff_period' => $this->cutoff_period,
            'earnings' => [
                'base_pay' => (float) $this->base_pay,
                'trip_earnings' => (float) $this->trip_earnings,
                'performance_bonus' => (float) $this->performance_bonus,
                'gross_pay' => (float) $this->gross_pay,
            ],
            'deductions' => [
                'sss' => (float) $this->sss_deduction,
                'philhealth' => (float) $this->philhealth_deduction,
                'pagibig' => (float) $this->pagibig_deduction,
                'total_deductions' => (float) $this->total_deductions,
            ],
            'net_pay' => (float) $this->net_pay,
            'status' => $this->status,
            'computation_breakdown' => $this->computation_breakdown,
        ];
    }
}
