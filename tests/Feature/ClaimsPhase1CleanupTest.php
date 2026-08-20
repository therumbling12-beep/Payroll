<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Services\Claims\ClaimGovernanceWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create([
        'name' => 'Operations Logistics',
        'code' => 'OPS-LOG',
    ]);

    $this->category = ClaimCategory::create([
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

    $this->driver = Employee::create([
        'employee_code' => 'DRV-PH1-01',
        'first_name' => 'Danilo',
        'last_name' => 'Navarro',
        'email' => 'danilo.navarro@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);
});

test('Dead admin filing routes are completely removed from route collection', function () {
    expect(Route::has('claims.expenses.fuel'))->toBeFalse()
        ->and(Route::has('claims.expenses.operational'))->toBeFalse()
        ->and(Route::has('claims.maternity.store'))->toBeFalse()
        ->and(Route::has('claims.medical.store'))->toBeFalse();
});

test('Dead FormRequest classes do not exist in app/Http/Requests', function () {
    expect(class_exists('App\Http\Requests\FuelReimbursementRequest'))->toBeFalse()
        ->and(class_exists('App\Http\Requests\OperationalExpenseRequest'))->toBeFalse()
        ->and(class_exists('App\Http\Requests\MaternityBenefitClaimRequest'))->toBeFalse()
        ->and(class_exists('App\Http\Requests\MedicalAssistanceClaimRequest'))->toBeFalse();
});

test('ClaimGovernanceWorkflowService operates exclusively on 4-gate enterprise lifecycle without supervisor method', function () {
    $service = app(ClaimGovernanceWorkflowService::class);
    expect(method_exists($service, 'approveSupervisor'))->toBeFalse();

    $claim = Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'amount' => 1800.00,
        'non_taxable_amount' => 1800.00,
        'taxable_amount' => 0.00,
        'receipt_number' => 'PH1-TEST-001',
        'approval_status' => 'pending_hr',
        'cutoff_period' => '2026-07-01_15',
    ]);

    // 1. HR Approval -> pending_admin
    $service->approveHR($claim, null, 'HR OK');
    $claim->refresh();
    expect($claim->approval_status)->toBe('pending_admin')
        ->and($claim->hr_approved_at)->not->toBeNull();

    // 2. Admin Approval -> pending_finance
    $service->approveAdmin($claim, null, 'Admin OK');
    $claim->refresh();
    expect($claim->approval_status)->toBe('pending_finance')
        ->and($claim->admin_approved_at)->not->toBeNull();

    // 3. Finance Approval -> approved
    $service->approveFinance($claim, null, 'Finance OK');
    $claim->refresh();
    expect($claim->approval_status)->toBe('approved')
        ->and($claim->finance_approved_at)->not->toBeNull();

    // 4. Queue to Payroll -> payroll_queued
    $service->queueToPayroll($claim);
    $claim->refresh();
    expect($claim->approval_status)->toBe('payroll_queued')
        ->and($claim->payroll_queued_at)->not->toBeNull();
});

test('ESS claim submission remains functional and creates pending_hr claim', function () {
    $response = $this->post(route('ess.claims.submit'), [
        'employee_id' => $this->driver->id,
        'type' => 'expense',
        'category_id' => $this->category->id,
        'amount' => 2200.00,
        'receipt_number' => 'ESS-PH1-VALID',
        'expense_date' => '2026-07-05',
        'description' => 'ESS filed operational fuel claim',
    ]);

    $response->assertRedirect();

    $claim = Claim::where('receipt_number', 'ESS-PH1-VALID')->first();
    expect($claim)->not->toBeNull()
        ->and($claim->approval_status)->toBe('pending_hr')
        ->and((float) $claim->amount)->toBe(2200.00);
});
