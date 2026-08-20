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

test('unified salary computation desk provides complete payslip generation and distribution capability', function () {
    $dept = Department::firstOrCreate(['name' => 'Logistics']);
    $employee = Employee::create([
        'employee_code' => 'DRV-2001',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@tripease.com',
        'department_id' => $dept->id,
        'position' => 'Senior Lead Driver',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
        'hire_date' => now()->subYears(1),
    ]);

    $cutoff = '2026-08-01_15';
    PayrollBatch::create(['cutoff_period' => $cutoff, 'status' => 'draft']);

    $computation = SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 14000.00,
        'gross_pay' => 14000.00,
        'total_deductions' => 1500.00,
        'net_pay' => 12500.00,
        'status' => 'pending_approval',
    ]);

    // 1. Verify desk renders action icons
    $response = $this->get(route('payroll.salary-computation.show', $cutoff));
    $response->assertOk();
    $response->assertSee(route('payroll.payslips.show', $computation->id));
    $response->assertSee(route('payroll.payslips.batch', $cutoff));

    // 2. Verify individual printable payslip view
    $payslipResponse = $this->get(route('payroll.payslips.show', $computation->id));
    $payslipResponse->assertOk();
    $payslipResponse->assertSee('Dela Cruz, Juan');
    $payslipResponse->assertSee('DRV-2001');

    // 3. Verify batch payslip view
    $batchResponse = $this->get(route('payroll.payslips.batch', $cutoff));
    $batchResponse->assertOk();
    $batchResponse->assertSee('Dela Cruz, Juan');
});
