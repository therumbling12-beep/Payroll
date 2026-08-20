<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('payroll engine calculates bank net pay strictly as gross minus deductions without cash reimbursement inflation', function () {
    $dept = Department::firstOrCreate(['name' => 'HR']);
    $employee = Employee::create([
        'employee_code' => 'EMP-TEST-01',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.test@tripease.com',
        'department_id' => $dept->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
        'hire_date' => now(),
    ]);

    // Create an approved cash reimbursement claim
    Claim::create([
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-06_12',
        'type' => 'expense',
        'amount' => 500.00,
        'description' => 'Gasoline Receipt',
        'receipt_number' => 'RCP-999',
        'approval_status' => 'approved',
        'status' => 'approved',
        'effective_date' => now(),
    ]);

    $service = app(PayrollEngineService::class);
    $comp = $service->computeForEmployee($employee, '2026-08-06_12');

    // Assert: Net Pay strictly equals Gross Pay minus Total Deductions
    $expectedNet = round((float)$comp->gross_pay - (float)$comp->total_deductions, 2);
    expect((float)$comp->net_pay)->toBe($expectedNet)
        ->and((float)$comp->reimbursements)->toBe(500.00);
});
