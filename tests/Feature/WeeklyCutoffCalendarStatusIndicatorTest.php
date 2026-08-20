<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('calendar indicates green ran badge for computed cutoffs and red not ran for pending cutoffs', function () {
    $department = \App\Models\Department::firstOrCreate(['name' => 'HR']);
    $employee = Employee::create([
        'employee_code' => 'EMP-TEST-01',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.com',
        'department_id' => $department->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
        'hire_date' => now(),
    ]);

    // Seed computation only for 2026-08-06_12
    SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-06_12',
        'base_pay' => 6923.08,
        'gross_pay' => 6923.08,
        'total_deductions' => 500.00,
        'net_pay' => 6423.08,
        'status' => 'pending_approval',
    ]);

    $response = $this->get(route('payroll.salary-computation'));

    $response->assertOk();
    $response->assertSee('Ran');
    $response->assertSee('Not Ran');
    $response->assertSee('bg-emerald-500');
    $response->assertSee('bg-rose-500');
});
