<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\ThirteenthMonthComputation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('13th month pay page renders clean employee table without static tab clutter', function () {
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

    ThirteenthMonthComputation::create([
        'year' => 2026,
        'employee_id' => $employee->id,
        'monthly_salary' => 32000.00,
        'months_worked' => 12,
        'amount' => 32000.00,
        'non_taxable_exempt' => 32000.00,
        'taxable_excess' => 0.00,
        'status' => 'pending_approval',
    ]);

    $response = $this->get(route('payroll.thirteenth-month', ['year' => 2026]));

    $response->assertOk();
    $response->assertSee('Maria Santos');
    $response->assertDontSee('Approval Workflow Trace');
    $response->assertDontSee('P.D. 851 & TRAIN Exemption Guide');
});
