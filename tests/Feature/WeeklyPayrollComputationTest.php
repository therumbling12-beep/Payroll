<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Administration', 'code' => 'ADM']);
    $this->user = User::factory()->create();

    // 1. Monthly Salaried Staff (26,000.00 / month -> 26,000 * 12 / 52 = 6,000.00 / week)
    $this->monthlyStaff = Employee::create([
        'employee_code' => 'EMP-SAL-01',
        'first_name' => 'Mariano',
        'last_name' => 'Ponce',
        'email' => 'mariano.p@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'HR Assistant',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    // 2. Daily Rated Staff (800.00 / day * 6 days = 4,800.00 / week)
    $this->dailyStaff = Employee::create([
        'employee_code' => 'EMP-DAY-01',
        'first_name' => 'Clara',
        'last_name' => 'Reyes',
        'email' => 'clara.r@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Dispatcher',
        'monthly_rate' => 0.00,
        'daily_rate' => 800.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);

    CompanySetting::setValue('payroll_frequency', 'weekly');
    CompanySetting::setValue('payroll_default_weekly_days_worked', '6');
    CompanySetting::setValue('payroll_standard_weeks_per_year', '52');
});

test('payroll engine computes weekly base pay for monthly-salaried employees', function () {
    $cutoff = '2026-08-13_19'; // Weekly Thursday-to-Wednesday cycle

    Attendance::create([
        'employee_id' => $this->monthlyStaff->id,
        'cutoff_period' => $cutoff,
        'days_worked' => 6,
        'regular_hours' => 48.0,
    ]);

    $engine = app(PayrollEngineService::class);
    $comp = $engine->computeForEmployee($this->monthlyStaff, $cutoff);

    // Monthly 26,000 x 12 / 52 = 6,000.00
    expect((float) $comp->base_pay)->toBe(6000.00)
        ->and((float) $comp->gross_pay)->toBeGreaterThanOrEqual(6000.00);
});

test('payroll engine computes weekly base pay for daily-rated employees based on days worked', function () {
    $cutoff = '2026-08-13_19';

    Attendance::create([
        'employee_id' => $this->dailyStaff->id,
        'cutoff_period' => $cutoff,
        'days_worked' => 6,
        'regular_hours' => 48.0,
    ]);

    $engine = app(PayrollEngineService::class);
    $comp = $engine->computeForEmployee($this->dailyStaff, $cutoff);

    // Daily Rate 800 x 6 days = 4,800.00
    expect((float) $comp->base_pay)->toBe(4800.00);
});

test('printable payslip renders weekly payroll header and thursday to wednesday cutoff', function () {
    $this->actingAs($this->user);
    $cutoff = '2026-08-13_19';

    $computation = SalaryComputation::create([
        'employee_id' => $this->monthlyStaff->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 6000.00,
        'gross_pay' => 6000.00,
        'net_pay' => 5400.00,
        'status' => 'pending_approval',
    ]);

    $response = $this->get(route('payroll.payslips.show', ['computation' => $computation->id]));
    $response->assertOk()
        ->assertSee('Weekly')
        ->assertSee('6,000.00');
});
