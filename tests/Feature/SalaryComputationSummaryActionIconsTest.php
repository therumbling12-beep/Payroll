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

test('salary computation summary view renders icon-only action buttons including direct payslip link', function () {
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
        'total_deductions' => 1850.00,
        'net_pay' => 14150.00,
        'status' => 'pending_approval',
    ]);

    $response = $this->get(route('payroll.salary-computation.show', $cutoff));

    $response->assertOk();
    $response->assertSee(route('payroll.payslips.show', $computation->id));
    $response->assertSee('View Printable Payslip');
    $response->assertSee('Detailed Formula Breakdown');
    $response->assertSee('Edit / Manual Override');
});
