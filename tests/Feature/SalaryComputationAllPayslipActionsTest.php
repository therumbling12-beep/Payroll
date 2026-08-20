<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\SalaryComputation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('end-to-end payslip workflow from salary computation desk to printable individual and batch payslips', function () {
    $dept = Department::firstOrCreate(['name' => 'Operations']);
    $employee = Employee::create([
        'employee_code' => 'EMP-1001',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripease.com',
        'department_id' => $dept->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'hire_date' => now()->subYears(2),
    ]);

    $cutoff = '2026-08-01_15';
    PayrollBatch::create(['cutoff_period' => $cutoff, 'status' => 'draft']);

    $computation = SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 16000.00,
        'gross_pay' => 16000.00,
        'sss_deduction' => 950.00,
        'philhealth_deduction' => 500.00,
        'pagibig_deduction' => 400.00,
        'total_deductions' => 1850.00,
        'net_pay' => 14150.00,
        'status' => 'pending_approval',
    ]);

    // 1. Visit Salary Computation Desk and verify action buttons
    $deskResponse = $this->get(route('payroll.salary-computation.show', $cutoff));
    $deskResponse->assertOk();
    $deskResponse->assertSee(route('payroll.payslips.show', $computation->id));
    $deskResponse->assertSee(route('payroll.payslips.batch', $cutoff));
    $deskResponse->assertSee('Batch Payslips');
    $deskResponse->assertSee('View Printable Payslip');

    // 2. Open Individual Digital Payslip and verify official layout & calculations
    $payslipResponse = $this->get(route('payroll.payslips.show', $computation->id));
    $payslipResponse->assertOk();
    $payslipResponse->assertSee('Santos, Maria');
    $payslipResponse->assertSee('EMP-1001');
    $payslipResponse->assertSee('16,000.00');
    $payslipResponse->assertSee('14,150.00');

    // 3. Open Batch Payslips Sheet and verify inclusion of employee
    $batchResponse = $this->get(route('payroll.payslips.batch', $cutoff));
    $batchResponse->assertOk();
    $batchResponse->assertSee('Santos, Maria');
    $batchResponse->assertSee('EMP-1001');
});
