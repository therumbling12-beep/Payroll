<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;
use Illuminate\Support\Facades\DB;

class ClaimsPayrollSyncService
{
    /**
     * Synchronize all approved claims into SalaryComputation for the target cutoff period
     *
     * @return array{
     *     cutoff_period: string,
     *     synced_claims_count: int,
     *     employees_updated_count: int,
     *     total_non_taxable_reimbursements: float,
     *     total_taxable_additions: float
     * }
     */
    public function syncApprovedClaimsToPayroll(string $cutoffPeriod, ?int $employeeId = null): array
    {
        return DB::transaction(function () use ($cutoffPeriod, $employeeId) {
            $query = Claim::where('cutoff_period', $cutoffPeriod)
                ->whereIn('approval_status', ['approved', 'payroll_queued'])
                ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId));

            $claims = $query->get();
            $grouped = $claims->groupBy('employee_id');

            $totalNonTaxable = 0.0;
            $totalTaxable = 0.0;
            $employeesUpdated = 0;

            foreach ($grouped as $empId => $empClaims) {
                $employee = Employee::find($empId);
                if (! $employee) {
                    continue;
                }

                $empNonTaxable = (float) $empClaims->sum('non_taxable_amount');
                $empTaxable = (float) $empClaims->sum('taxable_amount');

                $totalNonTaxable += $empNonTaxable;
                $totalTaxable += $empTaxable;

                $computation = SalaryComputation::where('employee_id', $empId)
                    ->where('cutoff_period', $cutoffPeriod)
                    ->first();

                if ($computation) {
                    $computation->reimbursements = $empNonTaxable;
                    if ($empTaxable > 0) {
                        $computation->performance_bonus = round(((float) $computation->performance_bonus) + $empTaxable, 2);
                        $computation->gross_pay = round(((float) $computation->gross_pay) + $empTaxable, 2);
                    }
                    $computation->net_pay = round(((float) $computation->gross_pay) - ((float) $computation->total_deductions), 2);
                    $computation->save();
                }

                $employeesUpdated++;
            }

            // Mark claims as queued to payroll
            Claim::whereIn('id', $claims->pluck('id'))->update([
                'approval_status' => 'payroll_queued',
                'payroll_queued_at' => now(),
            ]);

            PayrollAuditTrail::create([
                'action' => 'CLAIMS_BATCH_PAYROLL_SYNC',
                'model_type' => Claim::class,
                'model_id' => 0,
                'user_name' => 'Payroll Synchronization Engine',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => [],
                'new_values' => [
                    'cutoff_period' => $cutoffPeriod,
                    'claims_count' => $claims->count(),
                    'total_reimbursements' => $totalNonTaxable,
                    'total_taxable' => $totalTaxable,
                ],
            ]);

            return [
                'cutoff_period' => $cutoffPeriod,
                'synced_claims_count' => $claims->count(),
                'employees_updated_count' => $employeesUpdated,
                'total_non_taxable_reimbursements' => round($totalNonTaxable, 2),
                'total_taxable_additions' => round($totalTaxable, 2),
            ];
        });
    }
}
