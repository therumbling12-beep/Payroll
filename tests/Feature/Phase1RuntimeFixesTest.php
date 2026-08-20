<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
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
        'totalGrossPayroll',
        'totalClaimsDisbursed',
        'pendingClaimsCount',
    ]);
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
        ->and((float) $computation->net_pay)->toBe(20000.00);
});
