<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MealAllowanceDisbursement;
use App\Models\TripIncome;
use App\Models\User;
use App\Services\Benefits\MealAllowanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Logistics Fleet', 'code' => 'LFT']);
    $this->user = User::factory()->create();

    $this->driver = Employee::create([
        'employee_code' => 'DRV-MEAL-01',
        'first_name' => 'Danilo',
        'last_name' => 'Santos',
        'email' => 'danilo.s@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Express Driver',
        'monthly_rate' => 22000.00,
        'daily_rate' => 846.15,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    CompanySetting::setValue('meal_allowance_daily', '60.00');
    CompanySetting::setValue('meal_de_minimis_weekly_cap', '500.00');
});

test('meal allowance service calculates weekly subsidy from attendance records', function () {
    $cutoff = '2026-08-15_21';

    Attendance::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'days_worked' => 6,
        'regular_hours' => 48.0,
    ]);

    $service = app(MealAllowanceService::class);
    $data = $service->computeForEmployee($this->driver, $cutoff, 60.00);

    expect($data['days_rendered'])->toBe(6)
        ->and($data['gross_amount'])->toBe(360.00) // 6 * 60
        ->and($data['tax_exempt_amount'])->toBe(360.00) // Within 500 cap
        ->and($data['taxable_excess_amount'])->toBe(0.00);
});

test('meal allowance excess beyond BIR de minimis weekly ceiling is flagged as taxable', function () {
    $cutoff = '2026-08-15_21';

    Attendance::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'days_worked' => 6,
        'regular_hours' => 48.0,
    ]);

    $service = app(MealAllowanceService::class);
    // High subsidy of 120/day -> 6 * 120 = 720 PHP
    $data = $service->computeForEmployee($this->driver, $cutoff, 120.00);

    expect($data['gross_amount'])->toBe(720.00)
        ->and($data['tax_exempt_amount'])->toBe(500.00) // BIR De Minimis weekly cap
        ->and($data['taxable_excess_amount'])->toBe(220.00); // Excess above cap
});

test('meal allowance falls back to trips or standard benchmark if attendance is unrecorded', function () {
    $cutoff = '2026-08-15_21';

    TripIncome::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'total_trips' => 20,
        'total_trip_earnings' => 12000.00,
    ]);

    $service = app(MealAllowanceService::class);
    $data = $service->computeForEmployee($this->driver, $cutoff, 60.00);

    expect($data['days_rendered'])->toBe(5) // 20 trips / 4 = 5 shifts
        ->and($data['gross_amount'])->toBe(300.00);
});

test('hr can batch generate meal allowance disbursements for a cutoff period', function () {
    $this->actingAs($this->user);
    $cutoff = '2026-08-15_21';

    $response = $this->post(route('benefits.meal-allowance.generate'), [
        'cutoff_period' => $cutoff,
        'daily_rate' => 60.00,
    ]);

    $response->assertRedirect(route('benefits.meal-allowance', ['cutoff' => $cutoff]))
        ->assertSessionHas('status');

    $disbursement = MealAllowanceDisbursement::where('employee_id', $this->driver->id)
        ->where('cutoff_period', $cutoff)
        ->first();

    expect($disbursement)->not->toBeNull()
        ->and($disbursement->status)->toBe('pending')
        ->and((float) $disbursement->gross_amount)->toBeGreaterThan(0);
});

test('hr can approve and release meal allowance disbursement batch', function () {
    $this->actingAs($this->user);
    $cutoff = '2026-08-15_21';

    MealAllowanceDisbursement::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'days_rendered' => 6,
        'daily_subsidy_rate' => 60.00,
        'gross_amount' => 360.00,
        'tax_exempt_amount' => 360.00,
        'taxable_excess_amount' => 0.00,
        'status' => 'pending',
    ]);

    // 1. Approve
    $this->post(route('benefits.meal-allowance.approve'), ['cutoff_period' => $cutoff])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(MealAllowanceDisbursement::where('cutoff_period', $cutoff)->first()->status)->toBe('approved');

    // 2. Release
    $this->post(route('benefits.meal-allowance.release'), ['cutoff_period' => $cutoff])
        ->assertRedirect()
        ->assertSessionHas('status');

    $released = MealAllowanceDisbursement::where('cutoff_period', $cutoff)->first();
    expect($released->status)->toBe('released_to_payroll')
        ->and($released->disbursed_at)->not->toBeNull();
});

test('hr can export meal allowance roster csv with de minimis columns', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('benefits.meal-allowance.export'));
    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertStreamed();
});
