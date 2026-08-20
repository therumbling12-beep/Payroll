<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\User;
use App\Services\Claims\ClaimGovernanceWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Logistics', 'code' => 'LOG']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('phase 3: claims table defaults disbursement_method to cash and allows direct cash release with audit trail', function () {
    $employee = Employee::create([
        'employee_code' => 'EMP-CLM-01',
        'first_name' => 'Bongbong',
        'last_name' => 'Marcos',
        'email' => 'bbm@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Driver',
        'monthly_rate' => 0.00,
        'daily_rate' => 950.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);

    $claim = Claim::create([
        'employee_id' => $employee->id,
        'type' => 'expense',
        'expense_subtype' => 'fuel',
        'receipt_number' => 'GAS-2026-8811',
        'amount' => 1750.00,
        'non_taxable_amount' => 1750.00,
        'taxable_amount' => 0.00,
        'description' => 'Diesel fuel replenishment for Van 3',
        'approval_status' => 'pending_finance',
        'status' => 'pending',
    ]);

    // Check default disbursement_method is cash
    expect($claim->disbursement_method)->toBe('cash');
    expect($claim->isCashSettlement())->toBeTrue();
    expect($claim->cash_released_at)->toBeNull();

    // Execute direct cash release
    $governanceService = app(ClaimGovernanceWorkflowService::class);
    $governanceService->approveFinance($claim);
    $governanceService->releaseCash($claim, 'Settled from Logistics petty cash vault.');

    $claim->refresh();
    expect($claim->approval_status)->toBe('paid');
    expect($claim->status)->toBe('paid');
    expect($claim->cash_released_at)->not->toBeNull();
    expect($claim->paid_at)->not->toBeNull();

    // Verify audit trail entry was recorded
    $this->assertDatabaseHas('payroll_audit_trails', [
        'action' => 'CLAIM_CASH_DISBURSED',
        'model_type' => Claim::class,
        'model_id' => $claim->id,
    ]);
});

test('phase 3: claim workflow controller handles release_cash action successfully', function () {
    $employee = Employee::create([
        'employee_code' => 'EMP-CLM-02',
        'first_name' => 'Sara',
        'last_name' => 'Duterte',
        'email' => 'sara.d@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Operations Lead',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    $claim = Claim::create([
        'employee_id' => $employee->id,
        'type' => 'expense',
        'expense_subtype' => 'office_supplies',
        'receipt_number' => 'OFF-2026-1022',
        'amount' => 850.00,
        'non_taxable_amount' => 850.00,
        'taxable_amount' => 0.00,
        'description' => 'Printer ink cartridges',
        'disbursement_method' => 'cash',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    $response = $this->post(route('claims.workflow-action', $claim->id), [
        'action' => 'release_cash',
        'remarks' => 'Cash handed directly to claimant.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $claim->refresh();
    expect($claim->approval_status)->toBe('paid');
    expect($claim->cash_released_at)->not->toBeNull();
});
