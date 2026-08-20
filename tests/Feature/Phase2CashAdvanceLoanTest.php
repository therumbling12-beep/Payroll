<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\Payroll\LoanAmortizationService;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Operations', 'code' => 'OPS']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('phase 2: can record new cash advance loan via controller and format label correctly', function () {
    $employee = Employee::create([
        'employee_code' => 'EMP-CA-01',
        'first_name' => 'Rodrigo',
        'last_name' => 'Duterte',
        'email' => 'rodrigo.d@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Driver',
        'monthly_rate' => 0.00,
        'daily_rate' => 900.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subMonths(6),
    ]);

    $response = $this->post(route('payroll.loans.store'), [
        'employee_id' => $employee->id,
        'loan_type' => 'cash_advance',
        'reference_no' => 'CA-2026-9901',
        'principal_amount' => 4000.00,
        'total_amount_due' => 4000.00,
        'term_months' => 2,
        'semi_monthly_amortization' => 1000.00,
        'start_date' => '2026-08-01',
        'end_date' => '2026-09-30',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $loan = EmployeeLoan::where('reference_no', 'CA-2026-9901')->first();
    expect($loan)->not->toBeNull();
    expect($loan->loan_type)->toBe('cash_advance');
    expect($loan->loan_type_label)->toContain('Cash Advance');
    expect((float) $loan->principal_amount)->toEqual(4000.00);
    expect((float) $loan->remaining_balance)->toEqual(4000.00);
    expect($loan->status)->toBe('active');

    $this->assertDatabaseHas('employee_loans', [
        'reference_no' => 'CA-2026-9901',
        'loan_type' => 'cash_advance',
        'employee_id' => $employee->id,
    ]);
});

test('phase 2: payroll engine automatically computes and applies cash advance amortization deduction', function () {
    $employee = Employee::create([
        'employee_code' => 'EMP-CA-02',
        'first_name' => 'Leni',
        'last_name' => 'Robredo',
        'email' => 'leni.r@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);

    $loan = EmployeeLoan::create([
        'employee_id' => $employee->id,
        'loan_type' => 'cash_advance',
        'reference_no' => 'CA-2026-9902',
        'principal_amount' => 2000.00,
        'total_amount_due' => 2000.00,
        'term_months' => 1,
        'semi_monthly_amortization' => 1000.00,
        'total_paid' => 0.00,
        'remaining_balance' => 2000.00,
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-31',
        'status' => 'active',
    ]);

    $engine = app(PayrollEngineService::class);
    $computation = $engine->computeForEmployee($employee, '2026-08-13_19');

    // Loan deduction must match the 1000 amortization
    expect((float) $computation->loan_deduction)->toEqual(1000.00);

    // Apply deductions upon payroll release
    $loanService = app(LoanAmortizationService::class);
    $loanService->applyDeductions($computation);

    $loan->refresh();
    expect((float) $loan->total_paid)->toEqual(1000.00);
    expect((float) $loan->remaining_balance)->toEqual(1000.00);
    expect($loan->status)->toBe('active');

    // Verify amortization log was created
    $this->assertDatabaseHas('loan_amortization_logs', [
        'employee_loan_id' => $loan->id,
        'salary_computation_id' => $computation->id,
        'cutoff_period' => '2026-08-13_19',
        'amount_deducted' => 1000.00,
        'remaining_balance_after' => 1000.00,
    ]);
});
