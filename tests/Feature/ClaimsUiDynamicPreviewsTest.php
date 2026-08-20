<?php

declare(strict_types=1);

use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->dept = Department::create(['name' => 'Fleet Operations']);
    $this->driver = Employee::create([
        'employee_code' => 'DRV-999',
        'first_name' => 'Mario',
        'last_name' => 'Santos',
        'email' => 'mario.santos@example.com',
        'department_id' => $this->dept->id,
        'position' => 'Delivery Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 24000.00,
        'daily_rate' => 923.08,
    ]);

    ClaimCategory::create([
        'code' => 'FUEL-REIMB',
        'name' => 'Fuel Reimbursement',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'is_active' => true,
    ]);

    ClaimCategory::create([
        'code' => 'PERF-BONUS',
        'name' => 'Performance Incentive',
        'type' => 'incentive',
        'tax_classification' => 'taxable',
        'is_active' => true,
    ]);
});

test('Expenses view and ESS portal render dynamic fuel settings from CompanySetting', function () {
    // Set custom fuel settings in database
    CompanySetting::setValue('fuel_default_pump_price', 72.50);
    CompanySetting::setValue('fuel_tolerance_percentage', 12.00);

    $response = $this->get(route('claims.expenses'));
    $response->assertOk();
    $response->assertSee('12% Fuel Tolerance Active');

    $essResponse = $this->get(route('ess.dashboard', ['employee_id' => $this->driver->id]));
    $essResponse->assertOk();
    $essResponse->assertSee('claimFuelPrice: 72.5', false);
    $essResponse->assertSee('claimTolerancePct: 12', false);
});

// Compensation Bonus Allocation view test removed — bonus-allocation route removed (Phase 2, docs/no.md: bonuses N/A)

test('Claim categories policy setup renders dynamic settings from CompanySetting', function () {
    CompanySetting::setValue('fuel_default_pump_price', 78.50);
    CompanySetting::setValue('medical_de_minimis_annual_cap', 15000.00);

    $response = $this->get(route('claims.categories'));

    $response->assertOk();
    $response->assertSee('78.5');
    $response->assertSee('15000');
});
