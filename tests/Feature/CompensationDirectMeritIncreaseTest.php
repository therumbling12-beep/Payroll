<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CompanySetting::setValue('minimum_wage_daily', 755.00);
    $this->dept = Department::create(['name' => 'Fleet Logistics', 'code' => 'FLT']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('phase 2: apply direct merit increase updates employee rates and logs audit trail', function () {
    $employee = Employee::create([
        'employee_code' => 'EMP-MRT-01',
        'first_name' => 'Diego',
        'last_name' => 'Silang',
        'email' => 'diego.s@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Driver',
        'daily_rate' => 800.00,
        'monthly_rate' => 20800.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);

    $response = $this->post(route('compensation.direct-merit', $employee->id), [
        'new_daily_rate' => 880.00,
        'justification' => 'Quarterly zero-incident safety bonus merit adjustment',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $employee->refresh();
    expect((float) $employee->daily_rate)->toEqual(880.00);
    expect((float) $employee->monthly_rate)->toEqual(22880.00); // 880 * 26

    $this->assertDatabaseHas('payroll_audit_trails', [
        'action' => 'MERIT_INCREASE_APPLIED',
        'model_id' => $employee->id,
    ]);
});

test('phase 2: direct merit increase rejects rates below locality minimum wage floor', function () {
    $employee = Employee::create([
        'employee_code' => 'EMP-MRT-02',
        'first_name' => 'Gabriela',
        'last_name' => 'Silang',
        'email' => 'gabriela.s@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Dispatcher',
        'daily_rate' => 755.00,
        'monthly_rate' => 19630.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);

    $response = $this->post(route('compensation.direct-merit', $employee->id), [
        'new_daily_rate' => 600.00, // Below 755 floor
        'justification' => 'Invalid reduction',
    ]);

    $response->assertSessionHasErrors(['new_daily_rate']);
});
