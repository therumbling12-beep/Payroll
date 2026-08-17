<?php

declare(strict_types=1);

use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Claims\ClaimTaxabilityService;
use App\Services\Claims\FuelReimbursementValidationService;
use App\Services\Claims\MaternityBenefitService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fleetDept = Department::create(['name' => 'Fleet Operations']);
    $this->hrDept = Department::create(['name' => 'Human Resources']);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-001',
        'first_name' => 'Danilo',
        'last_name' => 'Cruz',
        'email' => 'danilo.cruz@example.com',
        'department_id' => $this->fleetDept->id,
        'position' => 'Senior Fleet Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
    ]);

    $this->officer = Employee::create([
        'employee_code' => 'OFF-001',
        'first_name' => 'Patricia',
        'last_name' => 'Lim',
        'email' => 'patricia.lim@example.com',
        'department_id' => $this->hrDept->id,
        'position' => 'HR Specialist',
        'employment_status' => 'regular',
        'monthly_rate' => 35000.00,
        'daily_rate' => 1346.15,
    ]);
});

test('Employee model correctly identifies drivers using isDriver and scopeDrivers', function () {
    expect($this->driver->isDriver())->toBeTrue()
        ->and($this->officer->isDriver())->toBeFalse();

    $drivers = Employee::drivers()->get();

    expect($drivers)->toHaveCount(1)
        ->and($drivers->first()->id)->toBe($this->driver->id);
});

test('FuelReimbursementValidationService dynamically adapts to custom CompanySetting values', function () {
    $service = app(FuelReimbursementValidationService::class);

    // 1. Test standard defaults (65 PHP/L, 10 km/L, 15% tolerance)
    $resDefault = $service->validateFuelClaim(1300.00, 200.0);
    // 200 km / 10 = 20 L * 65 = 1300 PHP -> 0% variance (auto_verified)
    expect($resDefault['is_within_tolerance'])->toBeTrue()
        ->and($resDefault['expected_cost'])->toBe(1300.00);

    // 2. Set custom pump price in CompanySetting (75 PHP/L) and custom tolerance (10%)
    CompanySetting::setValue('fuel_default_pump_price', 75.00);
    CompanySetting::setValue('fuel_tolerance_percentage', 10.00);

    $resCustom = $service->validateFuelClaim(1500.00, 200.0);
    // 200 km / 10 = 20 L * 75 = 1500 PHP -> 0% variance
    expect($resCustom['expected_cost'])->toBe(1500.00)
        ->and($resCustom['is_within_tolerance'])->toBeTrue();
});

test('MaternityBenefitService dynamically adapts to custom SSS MSC ceiling in CompanySetting', function () {
    $service = app(MaternityBenefitService::class);

    // High earning employee
    $this->officer->update(['monthly_rate' => 50000.00]);

    // 1. Default SSS MSC cap is 30,000
    $calcDefault = $service->computeMaternityBenefit($this->officer, 'normal_caesarean');
    expect($calcDefault['sss_msc'])->toBe(30000.00);

    // 2. Update SSS MSC ceiling to 35,000 in CompanySetting
    CompanySetting::setValue('sss_max_msc', 35000.00);

    $calcCustom = $service->computeMaternityBenefit($this->officer, 'normal_caesarean');
    expect($calcCustom['sss_msc'])->toBe(35000.00);
});

test('ClaimTaxabilityService dynamically adapts to custom Medical De Minimis annual cap in CompanySetting', function () {
    $service = app(ClaimTaxabilityService::class);

    $category = ClaimCategory::create([
        'code' => 'MED-DEM',
        'name' => 'Medical Outpatient',
        'type' => 'reimbursement',
        'tax_classification' => 'de_minimis',
        'is_active' => true,
    ]);

    // 1. Default statutory annual cap is 10,000
    $calcDefault = $service->classifyClaim($category, 12000.00, $this->officer);
    expect($calcDefault['annual_cap'])->toBe(10000.00)
        ->and($calcDefault['non_taxable_amount'])->toBe(10000.00)
        ->and($calcDefault['taxable_amount'])->toBe(2000.00);

    // 2. Update Medical de minimis cap to 15,000 in CompanySetting
    CompanySetting::setValue('medical_de_minimis_annual_cap', 15000.00);

    $calcCustom = $service->classifyClaim($category, 12000.00, $this->officer);
    expect($calcCustom['annual_cap'])->toBe(15000.00)
        ->and($calcCustom['non_taxable_amount'])->toBe(12000.00)
        ->and($calcCustom['taxable_amount'])->toBe(0.00);
});
