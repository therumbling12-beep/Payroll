<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\Payroll\ThirteenthMonthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->department = Department::create(['name' => 'Finance & Operations']);
    $this->service = app(ThirteenthMonthService::class);
});

test('example 1: full year employee with fixed salary earns exactly 1 full month pay', function () {
    // Example 1 from docs/PH_Government_Deductions_2026.md:
    // Monthly salary: ₱35,000, worked all 12 months -> ₱35,000 * 12 / 12 = ₱35,000
    $employee = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-EX1',
        'first_name' => 'Carlos',
        'last_name' => 'Mendoza',
        'email' => 'carlos.m@test.com',
        'position' => 'Accountant',
        'employment_status' => 'regular',
        'hire_date' => '2024-01-15',
        'monthly_rate' => 35000.00,
    ]);

    for ($m = 1; $m <= 12; $m++) {
        $mStr = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
        SalaryComputation::create([
            'employee_id' => $employee->id,
            'cutoff_period' => "2026-{$mStr}-01_07",
            'base_pay' => 35000.00,
            'gross_pay' => 35000.00,
            'total_deductions' => 3000.00,
            'net_pay' => 32000.00,
            'status' => 'released_financial',
        ]);
    }

    $result = $this->service->computeAnnual(2026, $employee->id);
    $data = $result['employees'][0];

    expect($data['amount'])->toBe(35000.00)
        ->and($data['non_taxable_exempt'])->toBe(35000.00)
        ->and($data['taxable_excess'])->toBe(0.00);
});

test('example 2: mid-year hire is pro-rated strictly by months worked', function () {
    // Example 2 from docs/PH_Government_Deductions_2026.md:
    // Monthly salary: ₱28,000, hired July 1 (6 months worked) -> ₱28,000 * 6 / 12 = ₱14,000
    $employee = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-EX2',
        'first_name' => 'Liza',
        'last_name' => 'So',
        'email' => 'liza.so@test.com',
        'position' => 'HR Assistant',
        'employment_status' => 'regular',
        'hire_date' => '2026-07-01',
        'monthly_rate' => 28000.00,
    ]);

    for ($m = 7; $m <= 12; $m++) {
        $mStr = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
        SalaryComputation::create([
            'employee_id' => $employee->id,
            'cutoff_period' => "2026-{$mStr}-01_07",
            'base_pay' => 28000.00,
            'gross_pay' => 28000.00,
            'total_deductions' => 2500.00,
            'net_pay' => 25500.00,
            'status' => 'released_financial',
        ]);
    }

    $result = $this->service->computeAnnual(2026, $employee->id);
    $data = $result['employees'][0];

    expect($data['months_worked'])->toBe(6)
        ->and($data['amount'])->toBe(14000.00)
        ->and($data['non_taxable_exempt'])->toBe(14000.00)
        ->and($data['taxable_excess'])->toBe(0.00);
});

test('example 3: employee with actual cutoffs earned computes exact sum divided by 12', function () {
    // Example 3 / 4 from docs/PH_Government_Deductions_2026.md:
    // Total Basic Salary actually earned across cutoffs = ₱408,000.00 -> ₱408,000 / 12 = ₱34,000.00
    $employee = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-EX3',
        'first_name' => 'Danilo',
        'last_name' => 'Cruz',
        'email' => 'danilo.c@test.com',
        'position' => 'Fleet Driver',
        'employment_status' => 'regular',
        'hire_date' => '2025-01-01',
        'monthly_rate' => 30000.00,
    ]);

    // Seed 12 cutoffs (Jan-Dec) with 34,000 basic pay each
    for ($m = 1; $m <= 12; $m++) {
        $mStr = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
        SalaryComputation::create([
            'employee_id' => $employee->id,
            'cutoff_period' => "2026-{$mStr}-01_15",
            'base_pay' => 34000.00,
            'trip_earnings' => 5000.00, // Should be ignored
            'overtime_pay' => 2000.00,  // Should be ignored
            'holiday_pay' => 1000.00,   // Should be ignored
            'gross_pay' => 42000.00,
            'net_pay' => 38000.00,
            'status' => 'released_financial',
        ]);
    }

    $result = $this->service->computeAnnual(2026, $employee->id);
    $data = $result['employees'][0];

    // Total base pay earned = 34,000 * 12 = 408,000 -> 13th month = 34,000.00
    expect($data['amount'])->toBe(34000.00)
        ->and($data['non_taxable_exempt'])->toBe(34000.00)
        ->and($data['taxable_excess'])->toBe(0.00);
});

test('train law 90k exemption ceiling isolates taxable excess for high compensation packages', function () {
    // 13th Month Pay = ₱120,000.00 -> Non-Taxable: ₱90,000.00, Taxable Excess: ₱30,000.00
    $executive = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-EXEC-01',
        'first_name' => 'Victor',
        'last_name' => 'Tan',
        'email' => 'victor.tan@test.com',
        'position' => 'Fleet Operations Director',
        'employment_status' => 'regular',
        'hire_date' => '2023-01-01',
        'monthly_rate' => 120000.00,
    ]);

    for ($m = 1; $m <= 12; $m++) {
        $mStr = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
        SalaryComputation::create([
            'employee_id' => $executive->id,
            'cutoff_period' => "2026-{$mStr}-01_07",
            'base_pay' => 120000.00,
            'gross_pay' => 120000.00,
            'total_deductions' => 10000.00,
            'net_pay' => 110000.00,
            'status' => 'released_financial',
        ]);
    }

    $result = $this->service->computeAnnual(2026, $executive->id);
    $data = $result['employees'][0];

    expect($data['amount'])->toBe(120000.00)
        ->and($data['non_taxable_exempt'])->toBe(90000.00)
        ->and($data['taxable_excess'])->toBe(30000.00);
});
