<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\Compensation\RetroactivePayCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('retroactive pay calculation validates cleanly without non-existent table error', function () {
    $user = User::factory()->create();
    $dept = Department::create(['name' => 'Operations', 'code' => 'OPS']);
    $emp = Employee::create([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@example.com',
        'employee_code' => 'EMP-TEST-001',
        'department_id' => $dept->id,
        'position' => 'Senior Fleet Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 25000.00,
        'daily_rate' => 961.54,
    ]);

    $response = $this->actingAs($user)->post(route('compensation.retroactive.calculate'), [
        'employee_id' => $emp->id,
        'new_monthly_rate' => 28000.00,
        'effective_date' => now()->subDays(15)->format('Y-m-d'),
        'days_worked' => 6,
        'inject_to_cutoff' => '2026-08-13_19',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'employee_id',
        'retroactive_pay',
        'daily_differential',
        'formula',
    ]);
});

test('compensation approval service correctly injects retroactive pay into weekly cutoff', function () {
    $dept = Department::create(['name' => 'Operations', 'code' => 'OPS']);
    $emp = Employee::create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@example.com',
        'employee_code' => 'EMP-TEST-002',
        'department_id' => $dept->id,
        'position' => 'Operations Dispatcher',
        'employment_status' => 'regular',
        'monthly_rate' => 20000.00,
    ]);

    $cutoffPeriod = '2026-08-13_19';

    $computation = SalaryComputation::create([
        'employee_id' => $emp->id,
        'cutoff_period' => $cutoffPeriod,
        'base_pay' => 4615.38,
        'gross_pay' => 4615.38,
        'total_deductions' => 500.00,
        'net_pay' => 4115.38,
        'status' => 'pending_approval',
    ]);

    $service = app(RetroactivePayCalculationService::class);
    $injected = $service->injectRetroactivePayToPayroll($emp, 500.00, $cutoffPeriod);

    expect($injected)->toBeTrue();

    $computation->refresh();
    expect((float) $computation->reimbursements)->toEqual(500.00)
        ->and((float) $computation->gross_pay)->toEqual(5115.38)
        ->and((float) $computation->net_pay)->toEqual(4615.38);
});
