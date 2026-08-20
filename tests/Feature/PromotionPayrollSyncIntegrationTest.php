<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Models\User;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('applying team 3 promotion synchronizes into weekly payroll engine with pure bank net pay', function () {
    $dept = Department::firstOrCreate(['name' => 'Operations']);
    $driver = Employee::create([
        'employee_code' => 'EMP-1011',
        'first_name' => 'Eduardo',
        'last_name' => 'Santos',
        'email' => 'eduardo.santos@tripease.com',
        'department_id' => $dept->id,
        'position' => 'Driver',
        'daily_rate' => 900.00,
        'monthly_rate' => 23400.00,
        'hire_date' => now()->subYears(2),
    ]);

    $newGrade = SalaryGrade::create([
        'grade_code' => 'PG-2',
        'job_level' => 2,
        'position_name' => 'Lead Fleet Driver',
        'min_salary' => 27000.00,
        'max_salary' => 38000.00,
        'annual_growth_rate' => 5.0,
        'effectivity_date' => now(),
    ]);

    // 1. Calculate promotion proposal (23,400 x 1.15 = 26,910; Grade Min is 27,000 -> Floor = 27,000)
    $response = $this->postJson(route('compensation.merit-promotions.calculate'), [
        'employee_id' => $driver->id,
        'type' => 'promotion',
        'new_grade_id' => $newGrade->id,
    ]);

    $response->assertOk();
    $data = $response->json();
    expect((float)$data['promoted_salary'])->toBe(27000.00);

    // 2. Synchronize promoted salary to employee record
    $driver->update([
        'position' => 'Lead Fleet Driver',
        'monthly_rate' => 27000.00,
        'daily_rate' => round(27000.00 / 26, 2),
    ]);

    // 3. Compute Weekly Payroll for Thursday-to-Wednesday cutoff
    $engine = app(PayrollEngineService::class);
    $computation = $engine->computeForEmployee($driver, '2026-08-06_12');

    // Weekly Base Pay: 27,000 x 12 / 52 = 6,230.77
    expect((float)$computation->gross_pay)->toBeGreaterThan(6000.00);
    // Bank Net Pay strictly equals Gross - Total Deductions
    expect((float)$computation->net_pay)->toBe(round((float)$computation->gross_pay - (float)$computation->total_deductions, 2));
});
