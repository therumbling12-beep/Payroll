<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoEnrollment;
use App\Models\SalaryComputation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('executive analytics overview page loads successfully with status 200', function () {
    $department = Department::create(['name' => 'Logistics', 'code' => 'LOG']);
    Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-001',
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'email' => 'alice@tripwise.test',
        'position' => 'Driver Lead',
        'monthly_rate' => 35000.00,
        'employment_status' => 'regular',
    ]);

    $response = $this->get(route('analytics.overview'));

    $response->assertStatus(200);
    $response->assertViewIs('payroll-benefits.analytics.overview');
    $response->assertViewHasAll([
        'totalEmployees',
        'totalDrivers',
        'totalStaff',
        'totalGrossPayroll',
        'totalNetPayroll',
        'totalDeductions',
        'activeHmoEnrolled',
        'totalClaimsDisbursed',
        'pendingClaimsCount',
    ]);
});

test('hr officer can validate hmo enrollment application via named route hr-validate', function () {
    $department = Department::create(['name' => 'HR', 'code' => 'HR']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-002',
        'first_name' => 'Bob',
        'last_name' => 'Taylor',
        'email' => 'bob@tripwise.test',
        'position' => 'HR Specialist',
        'monthly_rate' => 40000.00,
        'employment_status' => 'regular',
    ]);

    $enrollment = HmoEnrollment::create([
        'employee_id' => $employee->id,
        'hmo_card_number' => 'APP-TEST-001',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Maxicare Corporate',
        'coverage_tier' => 'Plus',
        'mbl_amount' => 150000.00,
        'annual_limit' => 150000.00,
        'monthly_premium' => 1800.00,
        'coverage_start_date' => '2026-08-01',
        'coverage_end_date' => '2027-07-31',
        'status' => 'inactive',
        'enrollment_status' => 'submitted',
    ]);

    $response = $this->post(route('hmo.enrollments.hr-validate', $enrollment), [
        'remarks' => 'Applicant meets full 6-month tenure requirement.',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'approvals']));
    $response->assertSessionHas('status');

    $enrollment->refresh();
    expect($enrollment->enrollment_status)->toBe('hr_approved')
        ->and($enrollment->hr_remarks)->toBe('Applicant meets full 6-month tenure requirement.')
        ->and($enrollment->hr_reviewed_at)->not->toBeNull();
});

test('hmo policy annual renewal executes successfully via named route enrollments.renew', function () {
    $department = Department::create(['name' => 'Fleet', 'code' => 'FLT']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-003',
        'first_name' => 'Charlie',
        'last_name' => 'Driver',
        'email' => 'charlie@tripwise.test',
        'position' => 'Express Driver',
        'monthly_rate' => 25000.00,
        'employment_status' => 'regular',
    ]);

    $enrollment = HmoEnrollment::create([
        'employee_id' => $employee->id,
        'hmo_card_number' => 'MAX-998877',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Fleet Care',
        'coverage_tier' => 'Driver Fleet Care',
        'mbl_amount' => 100000.00,
        'annual_limit' => 100000.00,
        'monthly_premium' => 1500.00,
        'coverage_start_date' => '2025-08-01',
        'coverage_end_date' => '2026-07-31',
        'status' => 'active',
        'enrollment_status' => 'active',
    ]);

    $response = $this->post(route('hmo.enrollments.renew', $enrollment));

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']));
    $response->assertSessionHas('status');

    $enrollment->refresh();
    expect($enrollment->status)->toBe('active')
        ->and($enrollment->renewed_at)->not->toBeNull()
        ->and($enrollment->coverage_end_date->format('Y-m-d'))->toBe('2027-07-31');
});

test('claims payroll sync route executes batch synchronization without database column errors', function () {
    $department = Department::create(['name' => 'Finance', 'code' => 'FIN']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-004',
        'first_name' => 'David',
        'last_name' => 'Miller',
        'email' => 'david@tripwise.test',
        'position' => 'Accountant',
        'monthly_rate' => 45000.00,
        'employment_status' => 'regular',
    ]);

    $cutoff = '2026-08-01_15';

    $computation = SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 22500.00,
        'trip_earnings' => 0.00,
        'performance_bonus' => 0.00,
        'gross_pay' => 22500.00,
        'total_deductions' => 2500.00,
        'reimbursements' => 0.00,
        'net_pay' => 20000.00,
        'status' => 'pending_approval',
    ]);

    $category = ClaimCategory::create([
        'name' => 'Office Supply Reimbursement',
        'code' => 'SUP-01',
        'type' => 'reimbursement',
        'tax_treatment' => 'non_taxable',
        'is_active' => true,
    ]);

    Claim::create([
        'employee_id' => $employee->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 800.00,
        'non_taxable_amount' => 800.00,
        'taxable_amount' => 0.00,
        'cutoff_period' => $cutoff,
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    $response = $this->post(route('claims.sync-payroll'), [
        'cutoff_period' => $cutoff,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $computation->refresh();
    expect((float) $computation->reimbursements)->toBe(800.00)
        ->and((float) $computation->net_pay)->toBe(20800.00);
});
