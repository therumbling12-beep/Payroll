<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Services\Claims\ClaimGovernanceWorkflowService;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create([
        'name' => 'Logistics Fleet Operations',
        'code' => 'OPS-FLT',
    ]);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-P3-01',
        'first_name' => 'Ramon',
        'last_name' => 'Bautista',
        'email' => 'ramon.bautista@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Delivery Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $this->gasCategory = ClaimCategory::create([
        'name' => 'Driver Gas Expense',
        'code' => 'CAT-DRV-GAS',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'color_tag' => 'orange',
        'max_amount' => 10000.00,
        'requires_receipt' => true,
        'applicable_to' => 'driver',
        'is_active' => true,
    ]);
});

test('ClaimGovernanceWorkflowService executes 4-gate sequential approval with dual status synchronization', function () {
    $service = app(ClaimGovernanceWorkflowService::class);

    $claim = Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->gasCategory->id,
        'type' => 'expense',
        'amount' => 2500.00,
        'non_taxable_amount' => 2500.00,
        'taxable_amount' => 0.00,
        'receipt_number' => 'OR-GOV-2026-001',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
        'cutoff_period' => '2026-07-01_15',
    ]);

    // Gate 1: HR Validation -> pending_admin
    $service->approveHR($claim, null, 'HR verified receipt and fuel route.');
    $claim->refresh();
    expect($claim->approval_status)->toBe('pending_admin')
        ->and($claim->status)->toBe('pending')
        ->and($claim->hr_approved_at)->not->toBeNull();

    // Gate 2: Admin Review & Authorization -> pending_finance
    $service->approveAdmin($claim, null, 'Admin authorized reimbursement.');
    $claim->refresh();
    expect($claim->approval_status)->toBe('pending_finance')
        ->and($claim->status)->toBe('pending')
        ->and($claim->admin_approved_at)->not->toBeNull();

    // Gate 3: Finance Budget Allocation -> approved
    $service->approveFinance($claim, null, 'Finance budget allocated.');
    $claim->refresh();
    expect($claim->approval_status)->toBe('approved')
        ->and($claim->status)->toBe('approved')
        ->and($claim->finance_approved_at)->not->toBeNull();

    // Gate 4: Payroll Queue -> payroll_queued
    $service->queueToPayroll($claim);
    $claim->refresh();
    expect($claim->approval_status)->toBe('payroll_queued')
        ->and($claim->status)->toBe('approved')
        ->and($claim->payroll_queued_at)->not->toBeNull();

    // Final Gate: Mark Paid -> paid
    $service->markPaid($claim);
    $claim->refresh();
    expect($claim->approval_status)->toBe('paid')
        ->and($claim->status)->toBe('paid')
        ->and($claim->paid_at)->not->toBeNull();
});

test('Claim rejection synchronously updates status and records mandatory rejection reason and audit trail', function () {
    $service = app(ClaimGovernanceWorkflowService::class);

    $claim = Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->gasCategory->id,
        'type' => 'expense',
        'amount' => 1200.00,
        'receipt_number' => 'OR-REJECT-001',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    $service->rejectClaim($claim, 'Unclear receipt photo; date of purchase is unreadable.', 'HR Reviewer');
    $claim->refresh();

    expect($claim->approval_status)->toBe('rejected')
        ->and($claim->status)->toBe('rejected')
        ->and($claim->rejection_reason)->toBe('Unclear receipt photo; date of purchase is unreadable.')
        ->and($claim->rejected_by)->toBe('HR Reviewer')
        ->and($claim->rejected_at)->not->toBeNull();
});

test('Partial approval reduces non-taxable and taxable amounts proportionally', function () {
    $service = app(ClaimGovernanceWorkflowService::class);

    $claim = Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->gasCategory->id,
        'type' => 'expense',
        'amount' => 2000.00,
        'non_taxable_amount' => 1600.00,
        'taxable_amount' => 400.00,
        'receipt_number' => 'OR-PARTIAL-001',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    // HR validates with partial amount ₱1,500.00 (75% ratio)
    $service->approveHR($claim, 1500.00, 'Deducted non-compliant personal expense.');
    $claim->refresh();

    expect((float) $claim->amount)->toBe(1500.00)
        ->and((float) $claim->non_taxable_amount)->toBe(1200.00) // 1600 * 0.75
        ->and((float) $claim->taxable_amount)->toBe(300.00)      // 400 * 0.75
        ->and($claim->hr_remarks)->toContain('Approved Amount Adjusted: ₱1,500.00');
});

test('Claim Eloquent scopes filter records accurately by governance state', function () {
    Claim::create([
        'employee_id' => $this->driver->id,
        'type' => 'expense',
        'amount' => 1000.00,
        'receipt_number' => 'SC-HR-01',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    Claim::create([
        'employee_id' => $this->driver->id,
        'type' => 'expense',
        'amount' => 1500.00,
        'receipt_number' => 'SC-ADM-01',
        'approval_status' => 'pending_admin',
        'status' => 'pending',
    ]);

    Claim::create([
        'employee_id' => $this->driver->id,
        'type' => 'expense',
        'amount' => 2000.00,
        'receipt_number' => 'SC-APP-01',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    Claim::create([
        'employee_id' => $this->driver->id,
        'type' => 'expense',
        'amount' => 2500.00,
        'receipt_number' => 'SC-Q-01',
        'approval_status' => 'payroll_queued',
        'status' => 'approved',
    ]);

    expect(Claim::needsAction()->count())->toBe(2)
        ->and(Claim::pendingHr()->count())->toBe(1)
        ->and(Claim::pendingAdmin()->count())->toBe(1)
        ->and(Claim::approved()->count())->toBe(1)
        ->and(Claim::payrollQueued()->count())->toBe(1)
        ->and(Claim::readyForPayroll()->count())->toBe(2);
});

test('PayrollEngineService incorporates approved and queued claims into gross and net pay', function () {
    $cutoff = '2026-07-01_15';

    Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->gasCategory->id,
        'type' => 'expense',
        'amount' => 1800.00,
        'non_taxable_amount' => 1800.00,
        'taxable_amount' => 0.00,
        'receipt_number' => 'OR-PAYROLL-001',
        'approval_status' => 'payroll_queued',
        'status' => 'approved',
        'cutoff_period' => $cutoff,
    ]);

    $payrollService = app(PayrollEngineService::class);
    $result = $payrollService->computeForEmployee($this->driver, $cutoff);

    expect((float) $result->reimbursements)->toBe(1800.00)
        ->and((float) $result->net_pay)->toBeGreaterThan(0.00);
});
