<?php

declare(strict_types=1);

namespace App\Services\Compensation;

use App\Models\CompensationAdjustment;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\PerformanceBonus;
use App\Models\SalaryComputation;
use App\Services\FinancialService;
use Illuminate\Support\Facades\DB;

class CompensationApprovalService
{
    public function __construct(
        protected FinancialService $financialService,
        protected RetroactivePayCalculationService $retroactivePayService,
        protected CounterOfferService $counterOfferService
    ) {}

    /**
     * Submit Compensation Adjustment for Team 5 Financial Budget Requisition Validation (§10.1, §13)
     */
    public function submitForFinanceValidation(CompensationAdjustment $adjustment): array
    {
        $monthlyCtc = (float) ($adjustment->monthly_ctc ?: ($adjustment->new_rate * 1.15));
        $deptName = $adjustment->employee?->department?->name ?? 'General';

        $check = $this->financialService->checkBudgetAvailability($monthlyCtc, $deptName);

        $status = $check['approved'] ? 'BUDGET_APPROVED' : 'BUDGET_REJECTED';

        $adjustment->update([
            'budget_impact_status' => $status,
        ]);

        PayrollAuditTrail::create([
            'action' => $check['approved'] ? 'FINANCE_BUDGET_APPROVED' : 'FINANCE_BUDGET_REJECTED',
            'model_type' => CompensationAdjustment::class,
            'model_id' => $adjustment->id,
            'user_name' => 'Team 5 Financial System',
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'old_values' => ['budget_impact_status' => 'PENDING_FINANCE_VALIDATION'],
            'new_values' => [
                'budget_impact_status' => $status,
                'monthly_ctc' => $monthlyCtc,
                'reason' => $check['reason'],
            ],
        ]);

        return [
            'status' => $status,
            'approved' => $check['approved'],
            'monthly_ctc' => $monthlyCtc,
            'reason' => $check['reason'],
        ];
    }

    /**
     * Grant Team 8 Administrative & Executive Approval (§6.8, §13)
     */
    public function grantAdminApproval(
        CompensationAdjustment $adjustment,
        string $adminName = 'Corporate Admin',
        ?string $adminNotes = null
    ): bool {
        return DB::transaction(function () use ($adjustment, $adminName, $adminNotes) {
            // Require Budget Clearance before Admin Approval
            if ($adjustment->budget_impact_status !== 'BUDGET_APPROVED') {
                $financeResult = $this->submitForFinanceValidation($adjustment);
                if (! $financeResult['approved']) {
                    return false;
                }
            }

            $adjustment->update([
                'admin_approval_status' => 'ADMIN_APPROVED',
                'admin_approved_by' => $adminName,
                'admin_approved_at' => now(),
                'admin_notes' => $adminNotes,
            ]);

            PayrollAuditTrail::create([
                'action' => 'ADMIN_APPROVAL_GRANTED',
                'model_type' => CompensationAdjustment::class,
                'model_id' => $adjustment->id,
                'user_name' => $adminName,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['admin_approval_status' => 'PENDING_ADMIN_APPROVAL'],
                'new_values' => [
                    'admin_approval_status' => 'ADMIN_APPROVED',
                    'admin_approved_by' => $adminName,
                    'admin_notes' => $adminNotes,
                ],
            ]);

            return true;
        });
    }

    /**
     * Finalize Approval & Sync Directly to Active Payroll and Employee Master Ledger
     */
    public function finalizeAndSyncToPayroll(CompensationAdjustment $adjustment): bool
    {
        return DB::transaction(function () use ($adjustment) {
            // Guard: Must have Budget Approval and Admin Approval
            if ($adjustment->budget_impact_status !== 'BUDGET_APPROVED') {
                $financeResult = $this->submitForFinanceValidation($adjustment);
                if (! $financeResult['approved']) {
                    return false;
                }
            }

            if ($adjustment->admin_approval_status !== 'ADMIN_APPROVED') {
                $this->grantAdminApproval($adjustment);
            }

            $oldStatus = $adjustment->status;
            $newRate = (float) $adjustment->new_rate;

            $adjustment->update([
                'status' => 'approved',
            ]);

            $emp = $adjustment->employee;
            if ($emp && $newRate > 0) {
                $oldRate = (float) ($emp->monthly_rate ?: 0.0);
                $isDriver = str_contains(strtolower($emp->position ?? ''), 'driver');

                $empUpdates = [
                    'monthly_rate' => $newRate,
                ];

                if ($isDriver) {
                    $empUpdates['daily_rate'] = round($newRate / 26, 2);
                }

                if ($adjustment->new_position && $adjustment->new_position !== $emp->position) {
                    $empUpdates['position'] = $adjustment->new_position;
                }

                $emp->update($empUpdates);

                // Sync One-Time Bonus to PerformanceBonus table for immediate payroll pickup if present
                if ($adjustment->bonus_amount && (float) $adjustment->bonus_amount > 0) {
                    $currentCutoff = SalaryComputation::where('employee_id', $emp->id)->latest('cutoff_period')->value('cutoff_period') ?? '2026-08-13_19';

                    PerformanceBonus::firstOrCreate([
                        'employee_id' => $emp->id,
                        'cutoff_period' => $currentCutoff,
                        'bonus_amount' => (float) $adjustment->bonus_amount,
                    ], [
                        'reason' => 'Compensation Bonus: ' . ($adjustment->reason ?? 'Approved Compensation Adjustment'),
                    ]);
                }

                // Check retroactive pay if effective date precedes today
                $effectiveDate = $adjustment->effective_date ? \Carbon\Carbon::parse($adjustment->effective_date) : now();
                if ($effectiveDate->isPast() && $effectiveDate->diffInDays(now()) >= 1) {
                    $daysWorked = (int) $effectiveDate->diffInDays(now());
                    $retroDiff = $this->retroactivePayService->calculateRetroactiveDifferential(
                        $emp,
                        $newRate,
                        $effectiveDate->format('Y-m-d'),
                        $daysWorked
                    );

                    $cutoffPeriod = SalaryComputation::where('employee_id', $emp->id)->latest('cutoff_period')->value('cutoff_period') ?? '2026-08-13_19';
                    $this->retroactivePayService->injectRetroactivePayToPayroll(
                        $emp,
                        (float) $retroDiff['retroactive_pay'],
                        $cutoffPeriod
                    );
                }
            }

            PayrollAuditTrail::create([
                'action' => 'COMPENSATION_ADJUSTMENT_FINALIZED_PAYROLL_SYNC',
                'model_type' => CompensationAdjustment::class,
                'model_id' => $adjustment->id,
                'user_name' => 'HR Director / Payroll Lead',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => 'approved',
                    'synced_rate' => $newRate,
                    'employee_id' => $adjustment->employee_id,
                ],
            ]);

            return true;
        });
    }

    /**
     * Record Candidate / Employee Response (Accepted, Declined, Negotiating)
     */
    public function recordEmployeeResponse(CompensationAdjustment $adjustment, string $response): bool
    {
        return DB::transaction(function () use ($adjustment, $response) {
            $normalized = strtolower(trim($response));
            $valid = ['accepted', 'declined', 'negotiating', 'pending'];

            if (! in_array($normalized, $valid, true)) {
                $normalized = 'pending';
            }

            $oldResponse = $adjustment->employee_response;

            $adjustment->update([
                'employee_response' => $normalized,
            ]);

            if ($normalized === 'accepted' && $adjustment->status === 'approved') {
                $this->finalizeAndSyncToPayroll($adjustment);
            }

            PayrollAuditTrail::create([
                'action' => 'COMPENSATION_EMPLOYEE_RESPONSE_UPDATED',
                'model_type' => CompensationAdjustment::class,
                'model_id' => $adjustment->id,
                'user_name' => 'HR Operations',
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['employee_response' => $oldResponse],
                'new_values' => ['employee_response' => $normalized],
            ]);

            return true;
        });
    }

    /**
     * Reject a Compensation Adjustment with Mandatory Reason
     */
    public function rejectAdjustment(
        CompensationAdjustment $adjustment,
        string $reason,
        string $rejector = 'Team 8 Admin'
    ): bool {
        return DB::transaction(function () use ($adjustment, $reason, $rejector) {
            $oldStatus = $adjustment->status;

            $adjustment->update([
                'status' => 'rejected',
                'admin_approval_status' => 'ADMIN_REJECTED',
                'admin_notes' => $reason,
            ]);

            PayrollAuditTrail::create([
                'action' => 'COMPENSATION_ADJUSTMENT_REJECTED',
                'model_type' => CompensationAdjustment::class,
                'model_id' => $adjustment->id,
                'user_name' => $rejector,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => 'rejected',
                    'reason' => $reason,
                    'rejected_by' => $rejector,
                ],
            ]);

            return true;
        });
    }
}
