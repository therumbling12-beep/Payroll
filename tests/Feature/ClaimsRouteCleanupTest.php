<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create([
        'name' => 'Operations Logistics',
        'code' => 'OPS-LOG',
    ]);

    $this->category = ClaimCategory::create([
        'name' => 'Driver Fuel Expense',
        'code' => 'CAT-FUEL-TEST',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'color_tag' => 'orange',
        'max_amount' => 5000.00,
        'requires_receipt' => true,
        'applicable_to' => 'driver',
        'is_active' => true,
    ]);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-CLEANUP-01',
        'first_name' => 'Ernesto',
        'last_name' => 'Dela Cruz',
        'email' => 'ernesto.delacruz@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Fleet Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);
});

test('Unified single claim action route executes all governance workflow stages seamlessly', function () {
    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'amount' => 2500.00,
        'non_taxable_amount' => 2500.00,
        'taxable_amount' => 0.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Route gas replenishment',
        'receipt_number' => 'RCP-UNIFIED-01',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    // 1. HR Approval
    $response = $this->post(route('claims.workflow-action', $claim->id), [
        'action' => 'approve_hr',
        'remarks' => 'HR verified receipts.',
    ]);
    $response->assertRedirect();
    $claim->refresh();
    expect($claim->approval_status)->toBe('pending_admin')
        ->and($claim->hr_approved_at)->not->toBeNull();

    // 2. Admin Approval
    $response = $this->post(route('claims.workflow-action', $claim->id), [
        'action' => 'approve_admin',
        'remarks' => 'Admin authorized.',
    ]);
    $response->assertRedirect();
    $claim->refresh();
    expect($claim->approval_status)->toBe('pending_finance')
        ->and($claim->admin_approved_at)->not->toBeNull();

    // 3. Finance Approval
    $response = $this->post(route('claims.workflow-action', $claim->id), [
        'action' => 'approve_finance',
        'remarks' => 'Budget allocated.',
    ]);
    $response->assertRedirect();
    $claim->refresh();
    expect($claim->approval_status)->toBe('approved')
        ->and($claim->finance_approved_at)->not->toBeNull();

    // 4. Queue to Payroll
    $response = $this->post(route('claims.workflow-action', $claim->id), [
        'action' => 'queue_payroll',
    ]);
    $response->assertRedirect();
    $claim->refresh();
    expect($claim->approval_status)->toBe('payroll_queued')
        ->and($claim->payroll_queued_at)->not->toBeNull();
});

test('Unified single claim action supports rejection with documented reason', function () {
    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'amount' => 1200.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Unverified repair receipt',
        'receipt_number' => 'RCP-REJECT-01',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    $response = $this->post(route('claims.workflow-action', $claim->id), [
        'action' => 'reject',
        'rejection_reason' => 'Receipt illegible and missing official receipt TIN number.',
    ]);

    $response->assertRedirect();
    $claim->refresh();

    expect($claim->approval_status)->toBe('rejected')
        ->and($claim->rejection_reason)->toContain('Receipt illegible');
});

test('Unified batch workflow handles multi-claim approvals in a single request', function () {
    $claim1 = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'amount' => 450.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Batch 1',
        'receipt_number' => 'RCP-B-01',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    $claim2 = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'amount' => 650.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Batch 2',
        'receipt_number' => 'RCP-B-02',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    $response = $this->post(route('claims.batch-workflow'), [
        'action' => 'batch_approve_hr',
        'selected_ids' => [$claim1->id, $claim2->id],
        'remarks' => 'Batch HR verification complete.',
    ]);

    $response->assertRedirect();
    $claim1->refresh();
    $claim2->refresh();

    expect($claim1->approval_status)->toBe('pending_admin')
        ->and($claim2->approval_status)->toBe('pending_admin');
});

test('Active payroll synchronization triggers properly through sync route', function () {
    Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'amount' => 1500.00,
        'non_taxable_amount' => 1500.00,
        'taxable_amount' => 0.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Sync Claim',
        'receipt_number' => 'RCP-SYNC-01',
        'approval_status' => 'payroll_queued',
        'status' => 'approved',
    ]);

    $response = $this->post(route('claims.sync-payroll'), [
        'cutoff_period' => '2026-07-01_15',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');
});

test('ESS bank details update route works via /ess/bank-details', function () {
    $response = $this->post(route('ess.bank-details'), [
        'employee_id' => $this->employee->id,
        'payment_method' => 'bank',
        'bank_name' => 'BDO Unibank',
        'bank_account_number' => '123456789012',
    ]);

    $response->assertRedirect();
    $this->employee->refresh();

    expect($this->employee->bank_name)->toBe('BDO Unibank')
        ->and($this->employee->bank_account_number)->toBe('123456789012');
});

test('Legacy approval and simulator routes are cleanly removed and return 404', function () {
    // 1. Legacy single approval route returns 404
    $res1 = $this->post('/claims/1/approve-hr', ['remarks' => 'test']);
    expect($res1->status())->toBe(404);

    // 2. Legacy batch route returns 404
    $res2 = $this->post('/claims/batch-approve-hr', ['selected_ids' => [1]]);
    expect($res2->status())->toBe(404);

    // 3. Removed API simulator route returns 404
    $res3 = $this->post('/claims/api/fuel-validator', ['amount' => 100]);
    expect($res3->status())->toBe(404);

    // 4. Duplicate ESS route returns 404
    $res4 = $this->post('/ess/update-bank-details', ['employee_id' => $this->employee->id]);
    expect($res4->status())->toBe(404);
});
