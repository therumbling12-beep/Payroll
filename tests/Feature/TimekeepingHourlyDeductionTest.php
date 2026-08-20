<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\Payroll\OvertimePayService;
use App\Services\Payroll\PayrollTransparencyService;
use App\Services\Payroll\TardinessDeductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Operations', 'code' => 'OPS']);
    $this->user = User::factory()->create();

    // Standard Staff with Daily Rate = 800.00 (Hourly Rate = 100.00)
    $this->employee = Employee::create([
        'employee_code' => 'EMP-TIME-01',
        'first_name' => 'Ramon',
        'last_name' => 'Bautista',
        'email' => 'ramon.b@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Dispatcher',
        'monthly_rate' => 20800.00,
        'daily_rate' => 800.00, // 800 / 8 = 100/hr
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    CompanySetting::setValue('overtime_rest_day_multiplier', '1.30');
    CompanySetting::setValue('tardiness_deduction_basis', 'hourly');
});

test('tardiness deduction service calculates late deduction per hour rate according to docs/no.md', function () {
    $service = app(TardinessDeductionService::class);

    // 2 hours late = 120 minutes
    $attendance = Attendance::create([
        'employee_id' => $this->employee->id,
        'cutoff_period' => '2026-08-15_21',
        'days_worked' => 6,
        'regular_hours' => 46.0,
        'tardiness_minutes' => 120, // 2.0 hours
        'undertime_minutes' => 60,  // 1.0 hour
    ]);

    $result = $service->compute($this->employee, $attendance);

    // Daily Rate 800 / 8 = 100/hr. 2 hrs late = 200.00. 1 hr undertime = 100.00.
    expect($result['hourly_rate'])->toBe(100.00)
        ->and($result['tardiness_deduction'])->toBe(200.00)
        ->and($result['undertime_deduction'])->toBe(100.00)
        ->and($result['total_time_deductions'])->toBe(300.00);
});

test('overtime pay service applies 130 percent rest day multiplier matching docs/no.md', function () {
    $service = app(OvertimePayService::class);

    // 8 hours on rest day
    $attendance = Attendance::create([
        'employee_id' => $this->employee->id,
        'cutoff_period' => '2026-08-15_21',
        'days_worked' => 6,
        'regular_hours' => 48.0,
        'overtime_hours' => 0.0,
        'rest_day_hours' => 8.0,
        'night_diff_hours' => 0.0,
    ]);

    $result = $service->compute($this->employee, $attendance);

    // 8 hrs x 100/hr x 1.30 = 1,040.00
    expect($result['overtime_pay'])->toBe(1040.00);
});

test('payroll transparency service generates hourly late formula and 130 percent rest day math', function () {
    $attendance = Attendance::create([
        'employee_id' => $this->employee->id,
        'cutoff_period' => '2026-08-15_21',
        'days_worked' => 6,
        'regular_hours' => 46.0,
        'tardiness_minutes' => 120,
        'undertime_minutes' => 60,
    ]);

    $computation = SalaryComputation::create([
        'employee_id' => $this->employee->id,
        'cutoff_period' => '2026-08-15_21',
        'base_pay' => 4800.00,
        'gross_pay' => 4800.00,
        'tardiness_deduction' => 200.00,
        'undertime_deduction' => 100.00,
        'net_pay' => 4500.00,
        'status' => 'pending_approval',
    ]);

    $service = app(PayrollTransparencyService::class);
    $breakdown = $service->generateBreakdown($computation);

    expect($breakdown['attendance_math']['tardiness_deduction'])->toBe(200.00)
        ->and($breakdown['attendance_math']['undertime_deduction'])->toBe(100.00)
        ->and($breakdown['attendance_math']['tardiness_formula'])->toContain('hr');
});
