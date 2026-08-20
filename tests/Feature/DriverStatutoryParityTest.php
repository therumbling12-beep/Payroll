<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->department = Department::create(['name' => 'Operations & Fleet']);
});

test('driver and regular staff with identical monthly basic rate receive identical statutory deductions', function () {
    $staff = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'STF-PARITY-01',
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'email' => 'ana.reyes@test.com',
        'position' => 'HR Assistant',
        'employment_status' => 'regular',
        'monthly_rate' => 20000.00,
        'payment_mode' => 'cash',
    ]);

    $driver = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'DRV-PARITY-01',
        'first_name' => 'Carlos',
        'last_name' => 'Dela Cruz',
        'email' => 'carlos.driver@test.com',
        'position' => 'Fleet Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 20000.00,
        'payment_mode' => 'cash',
    ]);

    $service = app(PayrollEngineService::class);
    $compStaff = $service->computeForEmployee($staff, '2026-08-01_15');
    $compDriver = $service->computeForEmployee($driver, '2026-08-01_15');

    // SSS on 20,000 monthly basis: (1000.00 * 12) / 52 = 230.77 weekly
    expect((float) $compDriver->sss_deduction)->toBe((float) $compStaff->sss_deduction)
        ->and((float) $compDriver->sss_deduction)->toBe(230.77);

    // PhilHealth on 20,000 monthly basis: (500.00 * 12) / 52 = 115.38 weekly
    expect((float) $compDriver->philhealth_deduction)->toBe((float) $compStaff->philhealth_deduction)
        ->and((float) $compDriver->philhealth_deduction)->toBe(115.38);

    // Pag-IBIG standard cap: (200.00 * 12) / 52 = 46.15 weekly
    expect((float) $compDriver->pagibig_deduction)->toBe((float) $compStaff->pagibig_deduction)
        ->and((float) $compDriver->pagibig_deduction)->toBe(46.15);
});

test('driver and regular staff with identical daily rate receive identical statutory deductions based on 26 days', function () {
    $staff = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'STF-DAILY-01',
        'first_name' => 'Elena',
        'last_name' => 'Gomez',
        'email' => 'elena.gomez@test.com',
        'position' => 'Dispatcher',
        'employment_status' => 'regular',
        'daily_rate' => 610.00, // 610 * 26 = 15,860.00 monthly basis
        'payment_mode' => 'cash',
    ]);

    $driver = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'DRV-DAILY-01',
        'first_name' => 'Danilo',
        'last_name' => 'Santos',
        'email' => 'danilo.driver@test.com',
        'position' => 'Fleet Driver',
        'employment_status' => 'regular',
        'daily_rate' => 610.00, // 610 * 26 = 15,860.00 monthly basis
        'payment_mode' => 'cash',
    ]);

    $service = app(PayrollEngineService::class);
    $compStaff = $service->computeForEmployee($staff, '2026-08-01_15');
    $compDriver = $service->computeForEmployee($driver, '2026-08-01_15');

    // Deductions must match 100%
    expect((float) $compDriver->sss_deduction)->toBe((float) $compStaff->sss_deduction)
        ->and((float) $compDriver->philhealth_deduction)->toBe((float) $compStaff->philhealth_deduction)
        ->and((float) $compDriver->pagibig_deduction)->toBe((float) $compStaff->pagibig_deduction);
});
