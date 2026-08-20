<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\TripIncome;
use App\Models\User;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->department = Department::create(['name' => 'Logistics & Fleet']);

    $this->driver = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'DRV-NOINC-01',
        'first_name' => 'Rolando',
        'last_name' => 'Cruz',
        'email' => 'rolando.noinc@test.com',
        'position' => 'Fleet Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 22000.00,
        'payment_mode' => 'cash',
    ]);
});

test('payroll engine calculates driver gross pay with zero trip incentives', function () {
    TripIncome::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => '2026-08-01_07',
        'completed_trips' => 45,
        'total_trip_earnings' => 4500.00,
    ]);

    $service = app(PayrollEngineService::class);
    $comp = $service->computeForEmployee($this->driver, '2026-08-01_07');

    // Driver trip incentive must strictly be 0.00 per client rules
    expect((float) $comp->driver_trip_incentive)->toBe(0.00);

    // Gross Pay = Base Pay + Trip Earnings + OT + Holiday + NSD (Zero incentives)
    $expectedGross = round((float) $comp->base_pay + 4500.00 + (float) $comp->overtime_pay + (float) $comp->holiday_pay + (float) $comp->night_diff_pay, 2);
    expect((float) $comp->gross_pay)->toBe($expectedGross);
});

test('manual batch update excludes driver trip incentives from gross pay calculation', function () {
    $comp = SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => '2026-08-01_07',
        'base_pay' => 5000.00,
        'trip_earnings' => 1500.00,
        'driver_trip_incentive' => 0.00,
        'overtime_pay' => 500.00,
        'holiday_pay' => 0.00,
        'night_diff_pay' => 0.00,
        'gross_pay' => 7000.00,
        'sss_deduction' => 500.00,
        'philhealth_deduction' => 250.00,
        'pagibig_deduction' => 100.00,
        'total_deductions' => 850.00,
        'net_pay' => 6150.00,
        'status' => 'pending_approval',
    ]);

    $response = $this->post(route('payroll.salary-computation.batch-update'), [
        'cutoff_period' => '2026-08-01_07',
        'computations' => [
            [
                'id' => $comp->id,
                'base_pay' => 5000.00,
                'trip_earnings' => 1500.00,
                'overtime_pay' => 500.00,
                'holiday_pay' => 0.00,
                'night_diff_pay' => 0.00,
                'sss_deduction' => 500.00,
                'philhealth_deduction' => 250.00,
                'pagibig_deduction' => 100.00,
                'loan_deduction' => 0.00,
                'tardiness_deduction' => 0.00,
                'undertime_deduction' => 0.00,
                'reimbursements' => 0.00,
            ],
        ],
    ]);

    $response->assertRedirect();
    $comp->refresh();

    // Gross: 5000 + 1500 + 500 = 7000.00 (Zero incentives)
    expect((float) $comp->gross_pay)->toBe(7000.00)
        ->and((float) $comp->driver_trip_incentive)->toBe(0.00);
});
