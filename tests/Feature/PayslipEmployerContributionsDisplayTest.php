<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('individual printable payslip renders employer statutory contributions and cash voucher notice', function () {
    $dept = Department::firstOrCreate(['name' => 'HR']);
    $employee = Employee::create([
        'employee_code' => 'EMP-TEST-02',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.test@tripease.com',
        'department_id' => $dept->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
        'hire_date' => now(),
    ]);

    $comp = SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-06_12',
        'base_pay' => 6923.08,
        'gross_pay' => 6923.08,
        'reimbursements' => 542.00,
        'sss_deduction' => 300.00,
        'sss_employer' => 600.00,
        'philhealth_deduction' => 170.00,
        'philhealth_employer' => 170.00,
        'pagibig_deduction' => 50.00,
        'pagibig_employer' => 50.00,
        'ec_contribution' => 10.00,
        'total_deductions' => 520.00,
        'net_pay' => 6403.08,
        'status' => 'pending_approval',
    ]);

    $response = $this->get(route('payroll.payslips.show', $comp->id));

    $response->assertOk();
    $response->assertSee('Employer Statutory Contributions');
    $response->assertSee('SSS Employer:');
    $response->assertSee('PhilHealth Employer:');
    $response->assertSee('Pag-IBIG Employer:');
    $response->assertSee('EC Contribution:');
    $response->assertSee('Over-the-Counter Cash Reimbursement Voucher');
    $response->assertSee('542.00');
});

test('batch printable payslips render employer contributions for entire cutoff', function () {
    $dept = Department::firstOrCreate(['name' => 'HR']);
    $employee = Employee::create([
        'employee_code' => 'EMP-TEST-03',
        'first_name' => 'Carlos',
        'last_name' => 'Reyes',
        'email' => 'carlos.test@tripease.com',
        'department_id' => $dept->id,
        'position' => 'Dispatcher',
        'monthly_rate' => 25000.00,
        'daily_rate' => 961.54,
        'hire_date' => now(),
    ]);

    SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-06_12',
        'base_pay' => 5769.23,
        'gross_pay' => 5769.23,
        'sss_deduction' => 260.00,
        'sss_employer' => 520.00,
        'philhealth_deduction' => 140.00,
        'philhealth_employer' => 140.00,
        'pagibig_deduction' => 50.00,
        'pagibig_employer' => 50.00,
        'ec_contribution' => 10.00,
        'total_deductions' => 450.00,
        'net_pay' => 5319.23,
        'status' => 'pending_approval',
    ]);

    $response = $this->get(route('payroll.payslips.batch', '2026-08-06_12'));

    $response->assertOk();
    $response->assertSee('Employer Statutory Contributions');
    $response->assertSee('SSS ER:');
});
