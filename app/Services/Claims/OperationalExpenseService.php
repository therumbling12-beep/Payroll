<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class OperationalExpenseService
{
    /**
     * File and store an operational expense claim (Toll, Maintenance, Parking, Meal, etc.)
     *
     * @param array<string, mixed> $data
     */
    public function fileOperationalClaim(array $data, ?UploadedFile $receiptFile = null): Claim
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $category = ClaimCategory::findOrFail($data['category_id']);
        $amount = (float) $data['amount'];

        $attachmentPath = null;
        if ($receiptFile) {
            $attachmentPath = $receiptFile->store('receipts/claims', 'public');
        }

        $subtype = $data['expense_subtype'] ?? match ($category->code) {
            'CAT-DRV-WORK' => 'toll',
            'CAT-TRANS' => 'toll',
            'CAT-MEAL' => 'meal',
            'CAT-COMM' => 'communication',
            'CAT-TRAIN' => 'other',
            default => 'other',
        };

        return DB::transaction(function () use ($data, $employee, $category, $amount, $subtype, $attachmentPath) {
            $claim = Claim::create([
                'employee_id' => $employee->id,
                'category_id' => $category->id,
                'category' => $category->name,
                'type' => 'expense',
                'expense_subtype' => $subtype,
                'receipt_number' => $data['receipt_number'] ?? ('EXP-' . strtoupper(uniqid())),
                'merchant_name' => $data['merchant_name'] ?? 'Official Merchant',
                'merchant_tin' => $data['merchant_tin'] ?? null,
                'amount' => $amount,
                'non_taxable_amount' => $amount, // 100% Non-Taxable Official Reimbursement
                'taxable_amount' => 0.00,
                'tax_classification' => 'non_taxable',
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'cutoff_period' => $data['cutoff_period'] ?? '2026-07-01_15',
                'description' => $data['description'] ?? sprintf(
                    '%s reimbursement from %s (%s).',
                    $category->name,
                    $data['merchant_name'] ?? 'Merchant',
                    $data['receipt_number'] ?? 'OR'
                ),
                'attachment_path' => $attachmentPath,
                'approval_status' => 'pending_hr',
                'status' => 'pending',
                'validation_status' => 'standard',
                'auto_validated' => false,
                'hr_remarks' => 'Submitted with official receipt for HR validation.',
            ]);

            PayrollAuditTrail::create([
                'action' => 'OPERATIONAL_EXPENSE_CLAIM_FILED',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => $employee->first_name . ' ' . $employee->last_name,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => [],
                'new_values' => [
                    'amount' => $amount,
                    'category' => $category->name,
                    'subtype' => $subtype,
                    'receipt_number' => $data['receipt_number'] ?? null,
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Compute dashboard summary statistics for all expense claims
     *
     * @return array{
     *     total_disbursed: float,
     *     fuel_claims_count: int,
     *     fuel_auto_validated_count: int,
     *     fuel_auto_validation_rate: float,
     *     flagged_variance_count: int,
     *     operational_claims_count: int,
     *     pending_count: int,
     *     approved_count: int
     * }
     */
    public function getExpenseSummaryStats(): array
    {
        $allExpenses = Claim::where('type', 'expense')->get();
        $fuelClaims = $allExpenses->where('expense_subtype', 'fuel');
        $autoValidatedCount = $fuelClaims->where('auto_validated', true)->count();
        $fuelTotal = $fuelClaims->count();

        $autoValidationRate = $fuelTotal > 0
            ? round(($autoValidatedCount / $fuelTotal) * 100, 1)
            : 100.0;

        $pendingClaims = $allExpenses->whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending']);
        $overdueCount = $pendingClaims->filter(fn (Claim $c) => $c->isOverdue())->count();

        return [
            'total_disbursed' => (float) $allExpenses->whereIn('approval_status', ['approved', 'payroll_queued', 'paid'])->sum('amount'),
            'fuel_claims_count' => $fuelTotal,
            'fuel_auto_validated_count' => $autoValidatedCount,
            'fuel_auto_validation_rate' => $autoValidationRate,
            'flagged_variance_count' => $fuelClaims->where('validation_status', 'flagged_variance')->count(),
            'operational_claims_count' => $allExpenses->where('expense_subtype', '!=', 'fuel')->count(),
            'pending_count' => $pendingClaims->count(),
            'overdue_count' => $overdueCount,
            'approved_count' => $allExpenses->whereIn('approval_status', ['approved', 'payroll_queued', 'paid'])->count(),
        ];
    }
}
