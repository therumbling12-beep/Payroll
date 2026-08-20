<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\Payroll\PagIbigContributionService;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Operations', 'code' => 'OPS']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('phase 1: pagibig service computes voluntary monthly contribution higher than 200 ceiling', function () {
    $service = app(PagIbigContributionService::class);

    // Standard 26,000 monthly basic: mandatory EE share is 200.00 monthly (100.00 semi-monthly)
    $standard = $service->compute(26000.00, true);
    expect($standard['employee_share'])->toEqual(100.00);
    expect($standard['employer_share'])->toEqual(100.00);

    // Employee voluntarily requests 600.00 monthly (300.00 semi-monthly)
    $voluntary = $service->compute(26000.00, true, 600.00);
    expect($voluntary['employee_share'])->toEqual(300.00);
    expect($voluntary['employer_share'])->toEqual(100.00); // Employer share remains statutory cap
    expect($voluntary['total_contribution'])->toEqual(400.00);
});

test('phase 1: payroll engine correctly applies voluntary pagibig deduction in weekly pay runs', function () {
    $employee = Employee::create([
        'employee_code' => 'EMP-PAG-01',
        'first_name' => 'Apolinario',
        'last_name' => 'Mabini',
        'email' => 'mabini.a@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Legal & Compliance Officer',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
        'pagibig_voluntary_contribution' => 520.00, // 520 / 52 weeks = 10.00 / 120 per month
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);

    $engine = app(PayrollEngineService::class);
    $comp = $engine->computeForEmployee($employee, '2026-08-13_19');

    // Expected weekly Pag-IBIG deduction: round((520 * 12) / 52, 2) = 120.00
    expect((float) $comp->pagibig_deduction)->toEqual(120.00);
    expect((float) $comp->pagibig_employer)->toEqual(46.15); // Standard weekly ER cap: round((200 * 12) / 52, 2)
});

test('phase 1: fallback to standard pagibig share when employee has no voluntary contribution', function () {
    $employee = Employee::create([
        'employee_code' => 'EMP-PAG-02',
        'first_name' => 'Melchora',
        'last_name' => 'Aquino',
        'email' => 'tandang.sora@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Staff Nurse',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
        'pagibig_voluntary_contribution' => null,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    $engine = app(PayrollEngineService::class);
    $comp = $engine->computeForEmployee($employee, '2026-08-13_19');

    // Standard weekly Pag-IBIG: round((200 * 12) / 52, 2) = 46.15
    expect((float) $comp->pagibig_deduction)->toEqual(46.15);
    expect((float) $comp->pagibig_employer)->toEqual(46.15);
});
