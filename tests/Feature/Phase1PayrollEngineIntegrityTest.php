<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceBonus;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Administration', 'code' => 'ADM']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('phase 1: payroll engine correctly computes and persists philhealth employer share without undefined variable', function () {
    $employee = Employee::create([
        'employee_code' => 'EMP-TEST-01',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'HR Assistant',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    $service = app(PayrollEngineService::class);
    $computation = $service->computeForEmployee($employee, '2026-08-13_19');

    // Basic salary 26,000 monthly basis: PhilHealth is 5% split 50/50 (2.5% EE / 2.5% ER)
    expect($computation->philhealth_employer)->toBeGreaterThan(0.00);
    expect($computation->philhealth_deduction)->toBeGreaterThan(0.00);
    expect($computation->philhealth_employer)->toEqual($computation->philhealth_deduction);

    // Verify persisted directly in database
    $this->assertDatabaseHas('salary_computations', [
        'id' => $computation->id,
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-13_19',
        'philhealth_employer' => $computation->philhealth_employer,
        'philhealth_deduction' => $computation->philhealth_deduction,
    ]);
});

test('phase 1: payroll engine isolates discretionary bonuses by default when setting is unconfigured', function () {
    // Ensure the setting key is clean/unseeded
    CompanySetting::where('key', 'payroll_include_discretionary_bonuses')->delete();

    $employee = Employee::create([
        'employee_code' => 'EMP-TEST-02',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.dc@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Driver',
        'monthly_rate' => 0.00,
        'daily_rate' => 800.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);

    // Seed a discretionary performance bonus for this employee and cutoff
    PerformanceBonus::create([
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-13_19',
        'bonus_amount' => 3500.00,
    ]);

    $service = app(PayrollEngineService::class);
    $computation = $service->computeForEmployee($employee, '2026-08-13_19');

    // Contracted weekly gross pay: 800 daily rate * 6 standard weekly days = 4,800.00
    expect((float) $computation->performance_bonus)->toEqual(0.00);
    expect((float) $computation->gross_pay)->toEqual(4800.00);

    // Verify persisted record in database has performance_bonus as 0.00
    $this->assertDatabaseHas('salary_computations', [
        'id' => $computation->id,
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-13_19',
        'performance_bonus' => 0.00,
        'gross_pay' => 4800.00,
    ]);
});
