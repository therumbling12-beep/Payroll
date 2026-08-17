<?php

declare(strict_types=1);

use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\Claims\DriverMilestoneIncentiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->fleetDept = Department::create(['name' => 'Fleet Operations']);
    $this->driver = Employee::create([
        'employee_code' => 'DRV-777',
        'first_name' => 'Roberto',
        'last_name' => 'Gomez',
        'email' => 'roberto.gomez@example.com',
        'department_id' => $this->fleetDept->id,
        'position' => 'Senior TNVS Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
    ]);

    ClaimCategory::create([
        'code' => 'FUEL-EXP',
        'name' => 'Driver Fuel Expense',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'is_active' => true,
    ]);
});

test('HR can update claim policy settings via POST /claims/settings', function () {
    $payload = [
        'fuel_default_pump_price' => 70.00,
        'fuel_default_efficiency_kpl' => 11.5,
        'fuel_tolerance_percentage' => 12.5,
        'performance_bonus_multiplier' => 1800.00,
        'driver_consistency_bonus' => 750.00,
        'driver_attendance_bonus' => 600.00,
        'sss_max_msc' => 32000.00,
        'medical_de_minimis_annual_cap' => 12000.00,
        'milestone_tiers' => [
            1 => ['tier' => 1, 'min_rides' => 15, 'amount' => 450.00, 'label' => 'Tier 1 (15 Rides)'],
            2 => ['tier' => 2, 'min_rides' => 35, 'amount' => 950.00, 'label' => 'Tier 2 (35 Rides)'],
            3 => ['tier' => 3, 'min_rides' => 55, 'amount' => 1600.00, 'label' => 'Tier 3 (55 Rides)'],
            4 => ['tier' => 4, 'min_rides' => 75, 'amount' => 2700.00, 'label' => 'Tier 4 (75 Rides)'],
            5 => ['tier' => 5, 'min_rides' => 95, 'amount' => 4200.00, 'label' => 'Tier 5 (95+ Rides)'],
        ],
    ];

    $response = $this->post(route('claims.settings.update'), $payload);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    expect((float) CompanySetting::getValue('fuel_default_pump_price'))->toBe(70.00)
        ->and((float) CompanySetting::getValue('fuel_tolerance_percentage'))->toBe(12.5)
        ->and((float) CompanySetting::getValue('performance_bonus_multiplier'))->toBe(1800.00)
        ->and((float) CompanySetting::getValue('driver_consistency_bonus'))->toBe(750.00)
        ->and((float) CompanySetting::getValue('sss_max_msc'))->toBe(32000.00);

    // Verify DriverMilestoneIncentiveService immediately uses the updated custom tiers
    $service = app(DriverMilestoneIncentiveService::class);
    $tiers = $service->getTiers();

    expect($tiers[1]['min_rides'])->toBe(15)
        ->and($tiers[1]['amount'])->toBe(450.00);

    // Test driver with 15 completed rides qualifies under the new custom Tier 1
    $calc = $service->computeDriverIncentive($this->driver, 15);
    expect($calc['is_qualified'])->toBeTrue()
        ->and($calc['qualified_tier'])->toBe(1)
        ->and($calc['base_milestone_amount'])->toBe(450.00);
});

test('Policy settings update validates required numeric thresholds', function () {
    $invalidPayload = [
        'fuel_default_pump_price' => -10, // Invalid
        'fuel_default_efficiency_kpl' => 0, // Invalid
        'fuel_tolerance_percentage' => 150, // Exceeds max 100
        'performance_bonus_multiplier' => -500,
        'driver_consistency_bonus' => -1,
        'driver_attendance_bonus' => -1,
        'sss_max_msc' => 500, // Below min 1000
        'medical_de_minimis_annual_cap' => -100,
    ];

    $response = $this->post(route('claims.settings.update'), $invalidPayload);

    $response->assertSessionHasErrors([
        'fuel_default_pump_price',
        'fuel_default_efficiency_kpl',
        'fuel_tolerance_percentage',
        'performance_bonus_multiplier',
        'driver_consistency_bonus',
        'driver_attendance_bonus',
        'sss_max_msc',
        'medical_de_minimis_annual_cap',
    ]);
});

test('Categories page displays the Policy and Rates setup tab and current settings', function () {
    CompanySetting::setValue('fuel_default_pump_price', 68.00);

    $response = $this->get(route('claims.categories'));

    $response->assertOk();
    $response->assertSeeText('Policy & Rates Setup');
    $response->assertSee('value="68"', false);
    $response->assertSeeText('Save & Deploy Policy Settings');
});
