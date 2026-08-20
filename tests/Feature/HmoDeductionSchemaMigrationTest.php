<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Logistics', 'code' => 'LOG']);
    $this->driver = Employee::create([
        'employee_code' => 'DRV-SCHEMA-01',
        'first_name' => 'Arman',
        'last_name' => 'Dela Cruz',
        'email' => 'arman.delacruz@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Express Driver',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYear(),
    ]);

    $this->user = User::factory()->create();
});

test('salary_computations table does not contain hmo_insurance_deduction column', function () {
    expect(Schema::hasColumn('salary_computations', 'hmo_insurance_deduction'))->toBeFalse();
});

test('salary computation model can be created and saved without hmo_insurance_deduction field', function () {
    $computation = SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => '2026-08-01_15',
        'gross_pay' => 6000.00,
        'base_pay' => 6000.00,
        'net_pay' => 5500.00,
        'total_deductions' => 500.00,
        'sss_deduction' => 200.00,
        'philhealth_deduction' => 150.00,
        'pagibig_deduction' => 100.00,
        'withholding_tax' => 50.00,
        'status' => 'pending_approval',
    ]);

    expect($computation->exists)->toBeTrue()
        ->and((float) $computation->gross_pay)->toBe(6000.00)
        ->and((float) $computation->net_pay)->toBe(5500.00);
});

test('manual payroll encoding executes cleanly without hmo deduction column errors', function () {
    $response = $this->actingAs($this->user)->post(route('payroll.manual-compute'), [
        'employee_id' => $this->driver->id,
        'cutoff_period' => '2026-08-01_15',
        'base_pay' => 7000.00,
        'trip_earnings' => 0.00,
        'performance_bonus' => 0.00,
        'reimbursements' => 0.00,
        'sss_deduction' => 200.00,
        'philhealth_deduction' => 150.00,
        'pagibig_deduction' => 100.00,
        'withholding_tax' => 0.00,
        'status' => 'pending_approval',
    ]);

    $response->assertRedirect()
        ->assertSessionHas('status');

    $saved = SalaryComputation::where('employee_id', $this->driver->id)->latest()->first();
    expect($saved)->not->toBeNull()
        ->and((float) $saved->gross_pay)->toBe(7000.00);
});
