<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\PayrollAuditTrail;
use Illuminate\Support\Facades\DB;

class ClaimGovernanceWorkflowService
{
    /**
     * Helper: Apply partial approval reduction proportionally to non-taxable and taxable portions
     */
    protected function applyApprovedAmount(Claim $claim, ?float $approvedAmount): void
    {
        if ($approvedAmount !== null && $approvedAmount > 0 && $approvedAmount < (float) $claim->amount) {
            $originalAmount = (float) $claim->amount;
            $ratio = $approvedAmount / $originalAmount;

            $newNonTaxable = round(((float) $claim->non_taxable_amount) * $ratio, 2);
            $newTaxable = round($approvedAmount - $newNonTaxable, 2);

            $claim->amount = $approvedAmount;
            $claim->non_taxable_amount = $newNonTaxable;
            $claim->taxable_amount = $newTaxable;
        }
    }

    /**
     * Step 1: HR Management Validation (First Gate of 4-Step Lifecycle)
     */
    public function approveHR(Claim $claim, ?float $approvedAmount = null, string $remarks = ''): Claim
    {
        return DB::transaction(function () use ($claim, $approvedAmount, $remarks) {
            $this->applyApprovedAmount($claim, $approvedAmount);

            if ($approvedAmount !== null && $approvedAmount > 0 && $approvedAmount < (float) $claim->getOriginal('amount')) {
                $remarks = trim(($remarks ?: 'HR validated.') . ' (Approved Amount Adjusted: ₱' . number_format($approvedAmount, 2) . ')');
            }

            $claim->hr_approved_at = now();
            $claim->hr_remarks = $remarks ?: 'HR validated official receipts and category compliance.';
            $claim->approval_status = 'pending_admin';
            $claim->status = 'pending';
            $claim->save();

            PayrollAuditTrail::create([
                'action' => 'CLAIM_HR_APPROVED',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => 'HR Benefits Specialist',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['approval_status' => 'pending_hr'],
                'new_values' => [
                    'approval_status' => 'pending_admin',
                    'approved_amount' => (float) $claim->amount,
                    'remarks' => $claim->hr_remarks,
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Step 3: Team 8 Admin Review & Authorization
     */
    public function approveAdmin(Claim $claim, ?float $approvedAmount = null, string $remarks = ''): Claim
    {
        return DB::transaction(function () use ($claim, $approvedAmount, $remarks) {
            $this->applyApprovedAmount($claim, $approvedAmount);

            if ($approvedAmount !== null && $approvedAmount > 0 && $approvedAmount < (float) $claim->getOriginal('amount')) {
                $remarks = trim(($remarks ?: 'Admin authorized.') . ' (Approved Amount Adjusted: ₱' . number_format($approvedAmount, 2) . ')');
            }

            $claim->admin_approved_at = now();
            $claim->admin_remarks = $remarks ?: 'Executive authorization confirmed.';
            $claim->approval_status = 'pending_finance';
            $claim->status = 'pending';
            $claim->save();

            PayrollAuditTrail::create([
                'action' => 'CLAIM_ADMIN_APPROVED',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => 'System Admin / Executive',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['approval_status' => 'pending_admin'],
                'new_values' => [
                    'approval_status' => 'pending_finance',
                    'approved_amount' => (float) $claim->amount,
                    'remarks' => $claim->admin_remarks,
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Step 4: Team 5 Finance Budget Validation & Final Approval
     */
    public function approveFinance(Claim $claim, ?float $approvedAmount = null, string $remarks = ''): Claim
    {
        return DB::transaction(function () use ($claim, $approvedAmount, $remarks) {
            $this->applyApprovedAmount($claim, $approvedAmount);

            if ($approvedAmount !== null && $approvedAmount > 0 && $approvedAmount < (float) $claim->getOriginal('amount')) {
                $remarks = trim(($remarks ?: 'Finance approved.') . ' (Approved Amount Adjusted: ₱' . number_format($approvedAmount, 2) . ')');
            }

            $claim->finance_approved_at = now();
            $claim->finance_remarks = $remarks ?: 'Budget availability confirmed and allocated by Finance.';
            $claim->approval_status = 'approved';
            $claim->status = 'approved';
            $claim->save();

            PayrollAuditTrail::create([
                'action' => 'CLAIM_FINANCE_APPROVED',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => 'Finance Officer',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['approval_status' => 'pending_finance'],
                'new_values' => [
                    'approval_status' => 'approved',
                    'status' => 'approved',
                    'approved_amount' => (float) $claim->amount,
                    'remarks' => $claim->finance_remarks,
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Reject Claim with Mandatory Documented Remarks
     */
    public function rejectClaim(Claim $claim, string $rejectionReason, string $rejectedByRole = 'HR Reviewer'): Claim
    {
        return DB::transaction(function () use ($claim, $rejectionReason, $rejectedByRole) {
            $oldStatus = $claim->approval_status;

            $claim->status = 'rejected';
            $claim->approval_status = 'rejected';
            $claim->rejection_reason = trim($rejectionReason);
            $claim->rejected_by = $rejectedByRole;
            $claim->rejected_at = now();
            $claim->save();

            PayrollAuditTrail::create([
                'action' => 'CLAIM_REJECTED',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => $rejectedByRole,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['approval_status' => $oldStatus],
                'new_values' => [
                    'approval_status' => 'rejected',
                    'status' => 'rejected',
                    'rejection_reason' => $claim->rejection_reason,
                    'rejected_by' => $rejectedByRole,
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Step 5: Queue to Active Payroll
     */
    public function queueToPayroll(Claim $claim, ?string $cutoffPeriod = null): Claim
    {
        return DB::transaction(function () use ($claim, $cutoffPeriod) {
            if ($cutoffPeriod) {
                $claim->cutoff_period = $cutoffPeriod;
            }

            $claim->status = 'approved';
            $claim->approval_status = 'payroll_queued';
            $claim->payroll_queued_at = now();
            $claim->save();

            PayrollAuditTrail::create([
                'action' => 'CLAIM_QUEUED_TO_PAYROLL',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => 'Payroll Specialist',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['approval_status' => 'approved'],
                'new_values' => [
                    'approval_status' => 'payroll_queued',
                    'cutoff_period' => $claim->cutoff_period,
                    'reimbursements' => (float) $claim->non_taxable_amount,
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Step 6: Mark Claim as Disbursed / Paid
     */
    public function markPaid(Claim $claim): Claim
    {
        return DB::transaction(function () use ($claim) {
            $claim->status = 'paid';
            $claim->approval_status = 'paid';
            $claim->paid_at = now();
            $claim->save();

            PayrollAuditTrail::create([
                'action' => 'CLAIM_DISBURSED_PAID',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => 'Disbursement Officer',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['approval_status' => 'payroll_queued'],
                'new_values' => [
                    'approval_status' => 'paid',
                    'paid_at' => now()->toIso8601String(),
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Direct Cash Settlement / Reimbursement Disbursement (docs/no.md Line 200)
     */
    public function releaseCash(Claim $claim, string $remarks = ''): Claim
    {
        return DB::transaction(function () use ($claim, $remarks) {
            $oldStatus = $claim->approval_status;

            $claim->status = 'paid';
            $claim->approval_status = 'paid';
            $claim->cash_released_at = now();
            $claim->paid_at = now();
            $claim->save();

            PayrollAuditTrail::create([
                'action' => 'CLAIM_CASH_DISBURSED',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => auth()->user()?->name ?? 'Disbursement Officer',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['approval_status' => $oldStatus],
                'new_values' => [
                    'approval_status' => 'paid',
                    'disbursement_method' => 'cash',
                    'amount' => (float) $claim->amount,
                    'cash_released_at' => now()->toIso8601String(),
                    'remarks' => $remarks ?: 'Direct cash reimbursement settled from Petty Cash.',
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Batch process multiple claims across workflow stages
     *
     * @param array<int> $claimIds
     */
    public function batchProcessClaims(array $claimIds, string $action, string $role, ?string $remarks = null): int
    {
        $claims = Claim::whereIn('id', $claimIds)->get();
        $processedCount = 0;

        foreach ($claims as $claim) {
            match ($action) {
                'batch_approve_hr' => $this->approveHR($claim, null, $remarks ?? 'Batch HR Validated'),
                'batch_approve_finance' => $this->approveFinance($claim, null, $remarks ?? 'Batch Finance Approved'),
                'batch_approve_admin' => $this->approveAdmin($claim, null, $remarks ?? 'Batch Admin Authorized'),
                'batch_queue_payroll' => $this->queueToPayroll($claim),
                'batch_reject' => $this->rejectClaim($claim, $remarks ?? 'Batch Rejected by Reviewer', $role),
                default => null,
            };
            $processedCount++;
        }

        return $processedCount;
    }
}
