<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\Claim;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceBonus;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\PayrollEngineService;
use App\Services\Payroll\PayrollTransparencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Operations Fleet', 'code' => 'OPS']);
    $this->user = User::factory()->create();

    $this->driver = Employee::create([
        'employee_code' => 'DRV-ISOL-01',
        'first_name' => 'Eduardo',
        'last_name' => 'Santos',
        'email' => 'eduardo.s@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Senior Fleet Driver',
        'monthly_rate' => 0.00,
        'daily_rate' => 900.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    CompanySetting::setValue('payroll_frequency', 'weekly');
    CompanySetting::setValue('payroll_default_weekly_days_worked', '6');
});

test('payroll engine isolates contracted weekly pay and handles separate cash reimbursement claims', function () {
    $cutoff = '2026-08-13_19';

    Attendance::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'days_worked' => 6,
        'regular_hours' => 48.0,
    ]);

    // Create an approved cash expense claim (e.g. Fuel Reimbursement)
    Claim::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'type' => 'expense',
        'amount' => 750.00,
        'approval_status' => 'approved',
        'description' => 'Emergency Fuel Reimbursement',
    ]);

    $engine = app(PayrollEngineService::class);
    $comp = $engine->computeForEmployee($this->driver, $cutoff);

    // Basic pay = 900 * 6 = 5,400.00. Reimbursement = 750.00 (non-taxable cash voucher).
    expect((float) $comp->base_pay)->toBe(5400.00)
        ->and((float) $comp->reimbursements)->toBe(750.00)
        ->and((float) $comp->net_pay)->toBe(round(5400.00 - (float) $comp->total_deductions, 2));
});

test('payroll transparency service displays contracted gross and non-taxable cash reimbursement breakdown', function () {
    $cutoff = '2026-08-13_19';

    $computation = SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 5400.00,
        'gross_pay' => 5400.00,
        'reimbursements' => 750.00,
        'net_pay' => 6150.00,
        'status' => 'pending_approval',
    ]);

    $service = app(PayrollTransparencyService::class);
    $breakdown = $service->generateBreakdown($computation);

    expect($breakdown['holiday_ot_math']['reimbursements'])->toBe(750.00)
        ->and($breakdown['holiday_ot_math']['contracted_gross'])->toBe(5400.00);
});

test('printable payslip renders non-taxable cash reimbursement line item with clear distinction', function () {
    $this->actingAs($this->user);
    $cutoff = '2026-08-13_19';

    $computation = SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 5400.00,
        'gross_pay' => 5400.00,
        'reimbursements' => 750.00,
        'net_pay' => 6150.00,
        'status' => 'pending_approval',
    ]);

    $response = $this->get(route('payroll.payslips.show', ['computation' => $computation->id]));
    $response->assertOk()
        ->assertSee('750.00')
        ->assertSee('Cash Reimbursement Voucher')
        ->assertSee('Cash Settlement');
});
