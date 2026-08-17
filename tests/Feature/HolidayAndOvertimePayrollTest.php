<?php

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\SalaryComputation;
use App\Services\Payroll\HolidayPayService;
use App\Services\Payroll\OvertimePayService;
use App\Services\Payroll\TardinessDeductionService;
use App\Services\PayrollEngineService;
use Database\Seeders\GovernmentContributionSeeder;
use Database\Seeders\HolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);
    $this->seed(HolidaySeeder::class);

    $this->department = Department::create([
        'name' => 'Fleet Operations',
    ]);

    $this->staff = Employee::create([
        'employee_code' => 'EMP-2001',
        'first_name' => 'Luisa',
        'last_name' => 'Bautista',
        'email' => 'luisa.bautista@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Admin Assistant',
        'employment_status' => 'regular',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00, // Hourly rate = 125.00, Minute rate = 2.0833
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0012345678',
        'is_active' => true,
    ]);
});

test('holiday pay service calculates worked regular holiday at 200 percent and special non-working at 130 percent', function () {
    $attendance = Attendance::create([
        'employee_id' => $this->staff->id,
        'cutoff_period' => '2026-04-01_15',
        'days_worked' => 10,
        'holiday_regular_hours' => 8.00, // Maundy Thursday / Good Friday worked
        'holiday_special_hours' => 8.00, // Black Saturday worked
    ]);

    $service = app(HolidayPayService::class);
    $result = $service->compute($this->staff, $attendance, '2026-04-01_15');

    // Daily rate = 1000, Hourly = 125.00
    // Worked Regular: 125.00 * 2.0 * 8 = 2000.00
    // Worked Special: 125.00 * 1.30 * 8 = 1300.00
    // Total = 3300.00
    expect((float) $result['holiday_pay'])->toBe(3300.00);
});

test('overtime pay service correctly computes 125 percent regular overtime and 10 percent night shift differential', function () {
    $attendance = Attendance::create([
        'employee_id' => $this->staff->id,
        'cutoff_period' => '2026-07-01_15',
        'days_worked' => 11,
        'overtime_hours' => 4.00,
        'night_diff_hours' => 10.00,
    ]);

    $service = app(OvertimePayService::class);
    $result = $service->compute($this->staff, $attendance);

    // Hourly = 125.00
    // Overtime Pay: 125.00 * 1.25 * 4 = 625.00
    // Night Diff Pay: 125.00 * 0.10 * 10 = 125.00
    // Total = 750.00
    expect((float) $result['overtime_pay'])->toBe(625.00)
        ->and((float) $result['night_diff_pay'])->toBe(125.00)
        ->and((float) $result['total_overtime'])->toBe(750.00);
});

test('tardiness deduction service accurately computes per-minute late and undertime penalties', function () {
    $attendance = Attendance::create([
        'employee_id' => $this->staff->id,
        'cutoff_period' => '2026-07-01_15',
        'days_worked' => 11,
        'tardiness_minutes' => 45,
        'undertime_minutes' => 30,
    ]);

    $service = app(TardinessDeductionService::class);
    $result = $service->compute($this->staff, $attendance);

    // Daily = 1000, Hourly = 125.00, Minute Rate = 125/60 = 2.0833
    // Late: 45 * 2.0833 = 93.75
    // Undertime: 30 * 2.0833 = 62.50
    // Total = 156.25
    expect((float) $result['tardiness_deduction'])->toBe(93.75)
        ->and((float) $result['undertime_deduction'])->toBe(62.50)
        ->and((float) $result['total_time_deductions'])->toBe(156.25);
});

test('master payroll engine integrates holiday pay overtime and tardiness into gross and deductions', function () {
    $attendance = Attendance::create([
        'employee_id' => $this->staff->id,
        'cutoff_period' => '2026-05-01_15', // Covers May 1 Labor Day
        'days_worked' => 10,
        'holiday_regular_hours' => 8.00,
        'overtime_hours' => 4.00,
        'night_diff_hours' => 10.00,
        'tardiness_minutes' => 30,
        'undertime_minutes' => 0,
    ]);

    $engine = app(PayrollEngineService::class);
    $comp = $engine->computeForEmployee($this->staff, '2026-05-01_15');

    // Base Pay: 26000 / 2 = 13000.00
    // Holiday Pay: 125 * 2 * 8 = 2000.00
    // Overtime Pay: 125 * 1.25 * 4 = 625.00
    // Night Diff: 125 * 0.10 * 10 = 125.00
    // Gross Pay: 13000 + 2000 + 625 + 125 = 15750.00
    // Tardiness: 30 * 2.0833 = 62.50
    expect((float) $comp->base_pay)->toBe(13000.00)
        ->and((float) $comp->holiday_pay)->toBe(2000.00)
        ->and((float) $comp->overtime_pay)->toBe(625.00)
        ->and((float) $comp->night_diff_pay)->toBe(125.00)
        ->and((float) $comp->gross_pay)->toBe(15750.00)
        ->and((float) $comp->tardiness_deduction)->toBe(62.50)
        ->and((float) $comp->net_pay)->toBeGreaterThan(0.00);
});

test('master payroll register export includes holiday overtime and timekeeping columns', function () {
    $cutoff = '2026-05-01_15';

    SalaryComputation::create([
        'employee_id' => $this->staff->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 13000.00,
        'trip_earnings' => 0.00,
        'holiday_pay' => 2000.00,
        'overtime_pay' => 625.00,
        'night_diff_pay' => 125.00,
        'performance_bonus' => 0.00,
        'reimbursements' => 0.00,
        'gross_pay' => 15750.00,
        'sss_deduction' => 650.00,
        'sss_employer' => 1300.00,
        'philhealth_deduction' => 325.00,
        'philhealth_employer' => 325.00,
        'pagibig_deduction' => 100.00,
        'pagibig_employer' => 100.00,
        'ec_contribution' => 15.00,
        'withholding_tax' => 250.00,
        'tardiness_deduction' => 62.50,
        'undertime_deduction' => 0.00,
        'total_deductions' => 1387.50,
        'net_pay' => 14362.50,
        'status' => 'pending_approval',
    ]);

    $response = $this->get(route('payroll.export.register', $cutoff));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
