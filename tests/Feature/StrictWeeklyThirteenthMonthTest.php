<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\Payroll\ThirteenthMonthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('13th month calculates strictly from actual weekly earnings across 2026 without projections', function () {
    $dept = Department::create(['name' => 'Logistics Fleet']);

    $employee = Employee::create([
        'department_id' => $dept->id,
        'employee_code' => 'EMP-TEST-2026',
        'first_name' => 'Danilo',
        'last_name' => 'Cruz',
        'email' => 'danilo.cruz@tripwise.com',
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'hire_date' => '2024-01-01',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
    ]);

    $service = app(ThirteenthMonthService::class);

    // 1. Zero cutoffs in 2026 -> 0.00 amount
    $zeroResult = $service->computeAnnual(2026, $employee->id);
    expect($zeroResult['employees'][0]['amount'])->toBe(0.00);

    // 2. Exactly 1 week cutoff recorded in August 2026 (Base Pay = ₱7,384.62) -> ₱7,384.62 / 12 = ₱615.39
    SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-13_19',
        'base_pay' => 7384.62,
        'gross_pay' => 7384.62,
        'total_deductions' => 500.00,
        'net_pay' => 6884.62,
        'status' => 'released_financial',
    ]);

    $oneWeekResult = $service->computeAnnual(2026, $employee->id);
    expect($oneWeekResult['employees'][0]['amount'])->toBe(615.39)
        ->and($oneWeekResult['employees'][0]['non_taxable_exempt'])->toBe(615.39)
        ->and($oneWeekResult['employees'][0]['taxable_excess'])->toBe(0.00);
});

test('13th month controller allows filtering across years 2026 2027 and 2028', function () {
    $user = User::factory()->create();

    // Year 2026
    $res2026 = $this->actingAs($user)->get(route('payroll.thirteenth-month', ['year' => 2026]));
    $res2026->assertOk()->assertSee('2026');

    // Year 2027
    $res2027 = $this->actingAs($user)->get(route('payroll.thirteenth-month', ['year' => 2027]));
    $res2027->assertOk()->assertSee('2027');

    // Year 2028
    $res2028 = $this->actingAs($user)->get(route('payroll.thirteenth-month', ['year' => 2028]));
    $res2028->assertOk()->assertSee('2028');
});

test('13th month audit ledger returns 52-week matrix structure with weekly breakdowns', function () {
    $dept = Department::create(['name' => 'Operations']);
    $employee = Employee::create([
        'department_id' => $dept->id,
        'employee_code' => 'EMP-LEDGER-01',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripwise.com',
        'position' => 'Dispatcher',
        'employment_status' => 'regular',
        'hire_date' => '2025-01-01',
        'monthly_rate' => 26000.00,
    ]);

    SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-06_12',
        'base_pay' => 6000.00,
        'gross_pay' => 6000.00,
        'total_deductions' => 500.00,
        'net_pay' => 5500.00,
        'status' => 'released_financial',
    ]);

    $service = app(ThirteenthMonthService::class);
    $ledger = $service->getDetailedAuditLedger(2026, $employee->id);

    expect($ledger['monthly_breakdown'])->toHaveCount(12)
        ->and($ledger['audit_metrics']['cutoffs_recorded_count'])->toBe(1)
        ->and($ledger['audit_metrics']['annual_base_pay_basis'])->toBe(6000.00)
        ->and($ledger['audit_metrics']['calculated_amount'])->toBe(500.00);

    // Check August weekly breakdown
    $august = collect($ledger['monthly_breakdown'])->firstWhere('month_number', 8);
    expect($august['weeks'])->toHaveCount(5)
        ->and($august['weeks'][0]['base_pay'])->toBe(6000.00)
        ->and($august['month_total'])->toBe(6000.00);
});
