<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Services\Claims\ClaimsPayrollSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('claims payroll sync accurately updates performance_bonus, gross_pay, reimbursements and net_pay', function () {
    $department = Department::create(['name' => 'Operations', 'code' => 'OPS']);
    
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-TEST-001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@tripwise.test',
        'position' => 'Senior Driver',
        'monthly_rate' => 30000.00,
        'employment_status' => 'regular',
    ]);

    $cutoff = '2026-08-01_15';

    $computation = SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 15000.00,
        'trip_earnings' => 5000.00,
        'performance_bonus' => 1000.00,
        'gross_pay' => 21000.00,
        'total_deductions' => 2000.00,
        'reimbursements' => 0.00,
        'net_pay' => 19000.00,
        'status' => 'pending_approval',
    ]);

    $reimbursementCategory = ClaimCategory::create([
        'name' => 'Fuel Reimbursement',
        'code' => 'FUEL-01',
        'type' => 'reimbursement',
        'tax_treatment' => 'non_taxable',
        'is_active' => true,
    ]);

    $incentiveCategory = ClaimCategory::create([
        'name' => 'Trip Milestone Bonus',
        'code' => 'BONUS-01',
        'type' => 'incentive',
        'tax_treatment' => 'taxable',
        'is_active' => true,
    ]);

    // Create 1 approved non-taxable expense claim (PHP 1,500)
    $nonTaxableClaim = Claim::create([
        'employee_id' => $employee->id,
        'category_id' => $reimbursementCategory->id,
        'type' => 'expense',
        'amount' => 1500.00,
        'non_taxable_amount' => 1500.00,
        'taxable_amount' => 0.00,
        'cutoff_period' => $cutoff,
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    // Create 1 approved taxable incentive claim (PHP 2,500)
    $taxableClaim = Claim::create([
        'employee_id' => $employee->id,
        'category_id' => $incentiveCategory->id,
        'type' => 'incentive',
        'amount' => 2500.00,
        'non_taxable_amount' => 0.00,
        'taxable_amount' => 2500.00,
        'cutoff_period' => $cutoff,
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    $syncService = app(ClaimsPayrollSyncService::class);
    $result = $syncService->syncApprovedClaimsToPayroll($cutoff, $employee->id);

    expect($result['synced_claims_count'])->toBe(2)
        ->and($result['total_non_taxable_reimbursements'])->toBe(1500.00)
        ->and($result['total_taxable_additions'])->toBe(2500.00);

    $computation->refresh();

    // Verify performance_bonus was incremented by taxable amount (1000 + 2500 = 3500)
    expect((float) $computation->performance_bonus)->toBe(3500.00);

    // Verify gross_pay was incremented by taxable amount (21000 + 2500 = 23500)
    expect((float) $computation->gross_pay)->toBe(23500.00);

    // Verify reimbursements column was updated with non-taxable total
    expect((float) $computation->reimbursements)->toBe(1500.00);

    // Verify net_pay is (gross_pay - total_deductions) + reimbursements = (23500 - 2000) + 1500 = 23000.00
    expect((float) $computation->net_pay)->toBe(23000.00);

    // Verify claims were queued to payroll
    expect($nonTaxableClaim->fresh()->approval_status)->toBe('payroll_queued')
        ->and($nonTaxableClaim->fresh()->payroll_queued_at)->not->toBeNull()
        ->and($taxableClaim->fresh()->approval_status)->toBe('payroll_queued');
});
