<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->dept = Department::create(['name' => 'Logistics & Fleet']);
    $this->driver = Employee::create([
        'employee_code' => 'DRV-771',
        'first_name' => 'Danilo',
        'last_name' => 'Ramos',
        'email' => 'danilo.ramos@example.com',
        'department_id' => $this->dept->id,
        'position' => 'Delivery Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 24000.00,
        'daily_rate' => 923.08,
    ]);

    $this->category = ClaimCategory::create([
        'code' => 'FUEL-REIMB',
        'name' => 'Fuel Reimbursement',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'is_active' => true,
    ]);
});

test('Expenses page renders 1-click filter pills with accurate dynamic counts', function () {
    // 1 pending claim
    $claimPending = Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'expense_subtype' => 'fuel',
        'amount' => 1200.00,
        'status' => 'pending',
        'approval_status' => 'pending_hr',
        'receipt_number' => 'OR-TEST-001',
        'expense_date' => now()->toDateString(),
        'description' => 'Route dispatch fuel',
    ]);

    // 1 overdue claim (created 5 days ago)
    $claimOverdue = Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'expense_subtype' => 'fuel',
        'amount' => 1500.00,
        'status' => 'pending',
        'approval_status' => 'pending_hr',
        'receipt_number' => 'OR-TEST-002',
        'expense_date' => now()->subDays(5)->toDateString(),
        'description' => 'Overdue route fuel',
    ]);
    DB::table('claims')->where('id', $claimOverdue->id)->update(['created_at' => now()->subDays(5)]);

    // 1 approved claim
    Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'expense_subtype' => 'fuel',
        'amount' => 800.00,
        'status' => 'approved',
        'approval_status' => 'approved',
        'receipt_number' => 'OR-TEST-003',
        'expense_date' => now()->toDateString(),
        'description' => 'Approved fuel reimbursement',
    ]);

    $response = $this->get(route('claims.expenses'));
    $response->assertOk();
    $response->assertSeeText('Needs My Action');
    $response->assertSeeText('Waiting > 3 Days');
    $response->assertSeeText('Ready for Next Payroll');

    // Test ?status=needs_action
    $actionResponse = $this->get(route('claims.expenses', ['status' => 'needs_action']));
    $actionResponse->assertOk();
    $actionResponse->assertSee('OR-TEST-001');
    $actionResponse->assertSee('OR-TEST-002');
    $actionResponse->assertDontSee('OR-TEST-003');

    // Test ?aging=overdue
    $overdueResponse = $this->get(route('claims.expenses', ['aging' => 'overdue']));
    $overdueResponse->assertOk();
    $overdueResponse->assertSee('OR-TEST-002');
    $overdueResponse->assertDontSee('OR-TEST-001');

    // Test ?status=ready_payroll
    $payrollResponse = $this->get(route('claims.expenses', ['status' => 'ready_payroll']));
    $payrollResponse->assertOk();
    $payrollResponse->assertSee('OR-TEST-003');
    $payrollResponse->assertDontSee('OR-TEST-001');
});

test('Approver can 1-click validate a claim and forward to Finance', function () {
    $claim = Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'expense_subtype' => 'fuel',
        'amount' => 1200.00,
        'status' => 'pending',
        'approval_status' => 'pending_hr',
        'receipt_number' => 'OR-TEST-100',
        'expense_date' => now()->toDateString(),
        'description' => 'Route dispatch fuel',
    ]);

    $response = $this->post(route('claims.workflow-action', $claim->id), [
        'action' => 'approve_hr',
        'remarks' => 'Receipt verified and within gas cost tolerance.',
    ]);

    $response->assertRedirect();
    $claim->refresh();

    expect($claim->approval_status)->toBe('pending_admin')
        ->and($claim->hr_approved_at)->not->toBeNull();
});

test('Approver can batch validate multiple selected claims', function () {
    $claim1 = Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'amount' => 500.00,
        'status' => 'pending',
        'approval_status' => 'pending_hr',
        'receipt_number' => 'OR-BATCH-01',
        'expense_date' => now()->toDateString(),
        'description' => 'Toll fee',
    ]);

    $claim2 = Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'amount' => 750.00,
        'status' => 'pending',
        'approval_status' => 'pending_hr',
        'receipt_number' => 'OR-BATCH-02',
        'expense_date' => now()->toDateString(),
        'description' => 'Parking fee',
    ]);

    $response = $this->post(route('claims.batch-workflow'), [
        'action' => 'batch_approve_hr',
        'selected_ids' => [$claim1->id, $claim2->id],
    ]);

    $response->assertRedirect();
    $claim1->refresh();
    $claim2->refresh();

    expect($claim1->approval_status)->toBe('pending_admin')
        ->and($claim2->approval_status)->toBe('pending_admin');
});
