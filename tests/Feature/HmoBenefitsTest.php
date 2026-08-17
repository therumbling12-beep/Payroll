<?php

declare(strict_types=1);

use App\Models\AccidentClaim;
use App\Models\BenefitType;
use App\Models\BudgetRequisition;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoEnrollment;
use App\Models\HmoUtilizationLog;
use App\Models\SalaryComputation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create(['name' => 'Fleet Operations']);
    $this->officeDept = Department::create(['name' => 'Human Resources']);

    $this->driver = Employee::create([
        'employee_code' => 'EMP-DRV-001',
        'first_name' => 'Eduardo',
        'last_name' => 'Reyes',
        'email' => 'eduardo.reyes@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior TNVS Partner Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 35000.00,
        'hire_date' => now()->subYears(2),
        'regularization_date' => now()->subYears(2)->addMonths(6),
    ]);

    $this->staff = Employee::create([
        'employee_code' => 'EMP-OFF-002',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripwise.com',
        'department_id' => $this->officeDept->id,
        'position' => 'HR Specialist',
        'employment_status' => 'regular',
        'monthly_rate' => 28000.00,
        'hire_date' => now()->subYear(),
        'regularization_date' => now()->subMonths(6),
    ]);

    CompanySetting::updateOrCreate(
        ['key' => 'driver_benefit_contribution_rate'],
        ['value' => '0.03', 'description' => 'Driver Benefit Fund Contribution Rate']
    );
});

test('it renders all HMO & Benefits subpages successfully', function () {
    $this->get(route('hmo.plans'))->assertOk();
    $this->get(route('hmo.driver-insurance'))->assertOk();
    $this->get(route('hmo.benefit-types'))->assertRedirect(route('hmo.plans', ['tab' => 'catalog']));
    $this->get(route('hmo.cost-tracking'))->assertOk();
    $this->get(route('hmo.budget-requests'))->assertRedirect(route('hmo.cost-tracking', ['tab' => 'budget']));
});

test('it can enroll an employee into Medicard HMO plan', function () {
    $response = $this->post(route('hmo.enroll'), [
        'employee_id' => $this->staff->id,
        'hmo_provider' => 'Medicard',
        'provider_plan' => 'Medicard Plus Care',
        'coverage_tier' => 'Plus',
        'coverage_start_date' => now()->format('Y-m-d'),
        'coverage_end_date' => now()->addYear()->format('Y-m-d'),
        'annual_limit' => 150000.00,
        'monthly_premium' => 900.00,
        'dependent_count' => 1,
        'notes' => 'Regular employee standard enrollment.',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']));
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('hmo_enrollments', [
        'employee_id' => $this->staff->id,
        'hmo_provider' => 'Medicard',
        'coverage_tier' => 'Plus',
        'annual_limit' => 150000.00,
        'monthly_premium' => 900.00,
        'dependent_count' => 1,
        'status' => 'active',
    ]);
});

test('it can update an existing HMO enrollment', function () {
    $enrollment = HmoEnrollment::create([
        'employee_id' => $this->staff->id,
        'hmo_card_number' => 'MED-2026-9999',
        'hmo_provider' => 'Medicard',
        'provider_plan' => 'Medicard Standard',
        'coverage_tier' => 'Basic',
        'mbl_amount' => 100000.00,
        'annual_limit' => 100000.00,
        'monthly_premium' => 500.00,
        'dependent_count' => 0,
        'coverage_start_date' => now(),
        'coverage_end_date' => now()->addYear(),
        'status' => 'active',
    ]);

    $response = $this->post(route('hmo.update-enrollment', $enrollment), [
        'hmo_provider' => 'Medicard',
        'provider_plan' => 'Medicard Executive Plus',
        'coverage_tier' => 'Premium',
        'coverage_start_date' => now()->format('Y-m-d'),
        'coverage_end_date' => now()->addYear()->format('Y-m-d'),
        'annual_limit' => 200000.00,
        'monthly_premium' => 1400.00,
        'dependent_count' => 2,
        'status' => 'active',
        'notes' => 'Upgraded to Premium tier with 2 dependents.',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']));
    $this->assertDatabaseHas('hmo_enrollments', [
        'id' => $enrollment->id,
        'coverage_tier' => 'Premium',
        'annual_limit' => 200000.00,
        'monthly_premium' => 1400.00,
        'dependent_count' => 2,
    ]);
});

test('it logs benefits utilization and accurately updates remaining balance', function () {
    $enrollment = HmoEnrollment::create([
        'employee_id' => $this->staff->id,
        'hmo_card_number' => 'MED-2026-1234',
        'hmo_provider' => 'Medicard',
        'provider_plan' => 'Medicard Plus',
        'coverage_tier' => 'Plus',
        'mbl_amount' => 150000.00,
        'annual_limit' => 150000.00,
        'monthly_premium' => 900.00,
        'dependent_count' => 1,
        'coverage_start_date' => now(),
        'coverage_end_date' => now()->addYear(),
        'status' => 'active',
    ]);

    $response = $this->post(route('hmo.log-utilization'), [
        'employee_id' => $this->staff->id,
        'hmo_enrollment_id' => $enrollment->id,
        'benefit_type' => 'HMO — Emergency ER Visit',
        'service_provider' => "St. Luke's Medical Center",
        'utilized_amount' => 8500.00,
        'utilized_at' => now()->format('Y-m-d'),
        'description' => 'Emergency care and diagnostic tests',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']));
    $this->assertDatabaseHas('hmo_utilization_logs', [
        'employee_id' => $this->staff->id,
        'hmo_enrollment_id' => $enrollment->id,
        'utilized_amount' => 8500.00,
        'remaining_balance' => 141500.00,
    ]);

    expect($enrollment->fresh()->remainingBalance())->toBe(141500.00);
});

test('it files a driver accident claim with pending HR review status', function () {
    $response = $this->post(route('hmo.file-claim'), [
        'employee_id' => $this->driver->id,
        'incident_type' => 'Work Injury',
        'incident_date' => now()->subDay()->format('Y-m-d'),
        'bill_amount' => 12500.00,
        'description' => 'On-duty vehicle collision resulting in outpatient trauma care.',
        'documents_uploaded' => 1,
    ]);

    $response->assertRedirect(route('hmo.driver-insurance'));
    $this->assertDatabaseHas('accident_claims', [
        'employee_id' => $this->driver->id,
        'incident_type' => 'Work Injury',
        'bill_amount' => 12500.00,
        'workflow_status' => 'pending_hr',
        'hr_status' => 'pending',
    ]);
});

test('it progresses driver accident claim through full 3-step approval workflow', function () {
    $claim = AccidentClaim::create([
        'employee_id' => $this->driver->id,
        'incident_number' => 'ACCD-2026-7777',
        'incident_type' => 'Accident - Hospitalization',
        'incident_date' => now()->subDays(2),
        'bill_amount' => 25000.00,
        'description' => 'Emergency hospitalization during active transit.',
        'workflow_status' => 'pending_hr',
        'hr_status' => 'pending',
        'admin_status' => 'pending',
        'finance_status' => 'pending',
        'status' => 'pending',
    ]);

    // Step 1: HR Approval
    $hrResp = $this->post(route('hmo.claim.approve-hr', $claim), [
        'approved_amount' => 25000.00,
        'remarks' => 'HR verified hospital receipts and policy coverage.',
    ]);
    $hrResp->assertRedirect(route('hmo.driver-insurance'));
    $claim->refresh();
    expect($claim->workflow_status)->toBe('pending_admin')
        ->and($claim->hr_status)->toBe('approved')
        ->and((float) $claim->approved_amount)->toBe(25000.00);

    // Step 2: Admin Approval
    $adminResp = $this->post(route('hmo.claim.approve-admin', $claim), [
        'approved_amount' => 25000.00,
        'remarks' => 'Administrative clearance granted.',
    ]);
    $adminResp->assertRedirect(route('hmo.driver-insurance'));
    $claim->refresh();
    expect($claim->workflow_status)->toBe('pending_finance')
        ->and($claim->admin_status)->toBe('approved');

    // Step 3: Finance Release
    $finResp = $this->post(route('hmo.claim.approve-finance', $claim), [
        'approved_amount' => 25000.00,
        'remarks' => 'Disbursement authorized from driver accident fund.',
    ]);
    $finResp->assertRedirect(route('hmo.driver-insurance'));
    $claim->refresh();
    expect($claim->workflow_status)->toBe('approved')
        ->and($claim->finance_status)->toBe('approved')
        ->and($claim->status)->toBe('paid');
});

test('it can return an accident claim with revision remarks', function () {
    $claim = AccidentClaim::create([
        'employee_id' => $this->driver->id,
        'incident_number' => 'ACCD-2026-8888',
        'incident_type' => 'Work Injury',
        'incident_date' => now()->subDay(),
        'bill_amount' => 5000.00,
        'description' => 'Minor abrasion treatment.',
        'workflow_status' => 'pending_hr',
        'hr_status' => 'pending',
    ]);

    $response = $this->post(route('hmo.claim.return', $claim), [
        'remarks' => 'Please upload clear copy of official doctor prescription.',
    ]);

    $response->assertRedirect(route('hmo.driver-insurance'));
    $claim->refresh();
    expect($claim->workflow_status)->toBe('returned')
        ->and($claim->status)->toBe('returned')
        ->and($claim->hr_remarks)->toBe('Please upload clear copy of official doctor prescription.');
});

test('it can update the driver benefit contribution percentage', function () {
    $response = $this->post(route('hmo.update-contribution-rate'), [
        'rate' => 3.5,
    ]);

    $response->assertRedirect(route('hmo.driver-insurance'));
    expect(CompanySetting::getValue('driver_benefit_contribution_rate'))->toBe('0.035');
});

test('it can store and toggle benefit types in catalog', function () {
    $response = $this->post(route('hmo.store-benefit-type'), [
        'name' => 'Executive Wellness Program',
        'code' => 'exec_wellness',
        'category' => 'Health Insurance',
        'eligibility' => 'Department Heads and Executives',
        'min_tenure_months' => 12,
        'dependent_options' => 'Employee only',
        'description' => 'Annual gym membership and executive wellness allowance.',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('hmo.plans', ['tab' => 'catalog']));
    $this->assertDatabaseHas('benefit_types', [
        'code' => 'exec_wellness',
        'name' => 'Executive Wellness Program',
        'is_active' => true,
    ]);

    $benefit = BenefitType::where('code', 'exec_wellness')->first();
    $toggleResp = $this->post(route('hmo.toggle-benefit-type', $benefit));
    $toggleResp->assertRedirect(route('hmo.plans', ['tab' => 'catalog']));
    expect($benefit->fresh()->is_active)->toBeFalse();
});

test('it can submit and manage budget requisitions with Team 5', function () {
    $response = $this->post(route('hmo.submit-request'), [
        'category' => 'Annual HMO Corporate Premiums (Medicard)',
        'amount' => 450000.00,
        'justification' => 'Annual corporate policy renewal for all regular employees.',
    ]);

    $response->assertRedirect(route('hmo.cost-tracking', ['tab' => 'budget']));
    $this->assertDatabaseHas('budget_requisitions', [
        'category' => 'Annual HMO Corporate Premiums (Medicard)',
        'amount' => 450000.00,
        'status' => 'awaiting_approval',
    ]);

    $req = BudgetRequisition::where('category', 'Annual HMO Corporate Premiums (Medicard)')->first();

    // Finance Approve
    $this->post(route('hmo.update-budget-status', $req), ['status' => 'approved'])
        ->assertRedirect(route('hmo.cost-tracking', ['tab' => 'budget']));
    expect($req->fresh()->status)->toBe('approved');

    // Finance Mark Released
    $this->post(route('hmo.update-budget-status', $req), ['status' => 'released'])
        ->assertRedirect(route('hmo.cost-tracking', ['tab' => 'budget']));
    expect($req->fresh()->status)->toBe('released');
});
