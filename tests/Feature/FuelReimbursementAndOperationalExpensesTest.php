<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Claims\FuelReimbursementValidationService;
use App\Services\Claims\OperationalExpenseService;
use Database\Seeders\ClaimCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ClaimCategorySeeder::class);
    Storage::fake('public');

    $this->fleetDept = Department::create(['name' => 'Fleet Operations']);
    $this->adminDept = Department::create(['name' => 'Administration & HR']);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-EXP-01',
        'first_name' => 'Gabriel',
        'last_name' => 'Bautista',
        'email' => 'gabriel.bautista@tripwise.com',
        'department_id' => $this->fleetDept->id,
        'position' => 'TNVS Senior Driver',
        'monthly_rate' => 35000.00,
        'daily_rate' => 1346.15,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    $this->staff = Employee::create([
        'employee_code' => 'STF-EXP-02',
        'first_name' => 'Jessica',
        'last_name' => 'Lim',
        'email' => 'jessica.lim@tripwise.com',
        'department_id' => $this->adminDept->id,
        'position' => 'Fleet Coordinator',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);
});

test('FuelReimbursementValidationService computes expected cost and validates 15% tolerance rule', function () {
    $service = new FuelReimbursementValidationService();

    // 250 km at 10 km/L with PHP 65.00/L pump price -> Expected: 25 Liters * PHP 65 = PHP 1,625.00
    // Claim 1: PHP 1,750.00 -> Variance: +PHP 125.00 (+7.69% <= 15%) -> AUTO VERIFIED
    $result1 = $service->validateFuelClaim(1750.00, 250.0, 10.0, 65.0);

    expect($result1['estimated_liters'])->toBe(25.0)
        ->and($result1['expected_cost'])->toBe(1625.00)
        ->and($result1['variance_amount'])->toBe(125.00)
        ->and($result1['is_within_tolerance'])->toBeTrue()
        ->and($result1['auto_validated'])->toBeTrue()
        ->and($result1['validation_status'])->toBe('auto_verified');

    // Claim 2: PHP 2,300.00 -> Variance: +PHP 675.00 (+41.54% > 15%) -> FLAGGED VARIANCE
    $result2 = $service->validateFuelClaim(2300.00, 250.0, 10.0, 65.0);

    expect($result2['expected_cost'])->toBe(1625.00)
        ->and($result2['is_within_tolerance'])->toBeFalse()
        ->and($result2['auto_validated'])->toBeFalse()
        ->and($result2['validation_status'])->toBe('flagged_variance');
});

test('FuelReimbursementValidationService files fuel claim with receipt upload and non-taxable tag', function () {
    $service = new FuelReimbursementValidationService();
    $receipt = UploadedFile::fake()->create('petron_gas_receipt.jpg', 400, 'image/jpeg');

    $claim = $service->fileFuelClaim([
        'employee_id' => $this->driver->id,
        'amount' => 1650.00,
        'distance_traveled_km' => 250.0,
        'vehicle_fuel_efficiency_kpl' => 10.0,
        'fuel_pump_price' => 65.0,
        'receipt_number' => 'OR-PET-2026-9901',
        'merchant_name' => 'Petron Station Balintawak',
        'merchant_tin' => '123-456-789-000',
        'expense_date' => '2026-07-10',
    ], $receipt);

    expect($claim)->toBeInstanceOf(Claim::class)
        ->and($claim->type)->toBe('expense')
        ->and($claim->expense_subtype)->toBe('fuel')
        ->and($claim->tax_classification)->toBe('non_taxable')
        ->and((float) $claim->non_taxable_amount)->toBe(1650.00)
        ->and((float) $claim->taxable_amount)->toBe(0.00)
        ->and($claim->auto_validated)->toBeTrue()
        ->and($claim->validation_status)->toBe('auto_verified')
        ->and($claim->attachment_path)->not->toBeNull();

    Storage::disk('public')->assertExists($claim->attachment_path);
});

test('OperationalExpenseService files toll and maintenance claims as non-taxable reimbursements', function () {
    $service = new OperationalExpenseService();
    $tollCategory = ClaimCategory::where('code', 'CAT-DRV-WORK')->first();
    $receipt = UploadedFile::fake()->create('easytrip_rfid.pdf', 300, 'application/pdf');

    $claim = $service->fileOperationalClaim([
        'employee_id' => $this->driver->id,
        'category_id' => $tollCategory->id,
        'expense_subtype' => 'toll',
        'amount' => 850.00,
        'receipt_number' => 'RFID-EASY-2026-1049',
        'merchant_name' => 'EasyTrip RFID NLEX Plaza',
        'merchant_tin' => '987-654-321-000',
        'expense_date' => '2026-07-11',
        'description' => 'Expressway toll reload for North dispatch run.',
    ], $receipt);

    expect($claim)->toBeInstanceOf(Claim::class)
        ->and($claim->type)->toBe('expense')
        ->and($claim->expense_subtype)->toBe('toll')
        ->and($claim->tax_classification)->toBe('non_taxable')
        ->and((float) $claim->non_taxable_amount)->toBe(850.00)
        ->and((float) $claim->taxable_amount)->toBe(0.00)
        ->and($claim->attachment_path)->not->toBeNull();

    Storage::disk('public')->assertExists($claim->attachment_path);
});

test('POST /claims/expenses/fuel stores fuel claim with validation feedback', function () {
    $response = $this->post('/claims/expenses/fuel', [
        'employee_id' => $this->driver->id,
        'amount' => 1700.00,
        'distance_traveled_km' => 260.0,
        'vehicle_fuel_efficiency_kpl' => 10.0,
        'fuel_pump_price' => 65.0,
        'receipt_number' => 'OR-SHELL-2026-7788',
        'merchant_name' => 'Shell EDSA Station',
        'merchant_tin' => '000-111-222-333',
        'expense_date' => '2026-07-12',
        'description' => 'Routine route fuel top-up.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('claims', [
        'employee_id' => $this->driver->id,
        'receipt_number' => 'OR-SHELL-2026-7788',
        'expense_subtype' => 'fuel',
        'auto_validated' => true,
        'tax_classification' => 'non_taxable',
    ]);
});

test('POST /claims/expenses/operational stores operational expense claim', function () {
    $category = ClaimCategory::where('code', 'CAT-DRV-WORK')->first();

    $response = $this->post('/claims/expenses/operational', [
        'employee_id' => $this->driver->id,
        'category_id' => $category->id,
        'expense_subtype' => 'parking',
        'amount' => 350.00,
        'receipt_number' => 'PRK-DEPOT-2026-004',
        'merchant_name' => 'Manila Grand Depot Parking',
        'expense_date' => '2026-07-13',
        'description' => 'Overnight staging depot parking.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('claims', [
        'employee_id' => $this->driver->id,
        'receipt_number' => 'PRK-DEPOT-2026-004',
        'expense_subtype' => 'parking',
        'tax_classification' => 'non_taxable',
    ]);
});

test('FuelReimbursementValidationService returns live formula calculation and tolerance status', function () {
    $service = new FuelReimbursementValidationService();
    $result = $service->validateFuelClaim(1600.00, 240.0, 10.0, 65.0);

    expect($result['estimated_liters'])->toBe(24.0)
        ->and($result['expected_cost'])->toBe(1560.00)
        ->and($result['actual_amount'])->toBe(1600.00)
        ->and($result['is_within_tolerance'])->toBeTrue()
        ->and($result['auto_validated'])->toBeTrue()
        ->and($result['validation_status'])->toBe('auto_verified')
        ->and($result)->toHaveKeys([
            'distance_km',
            'efficiency_kpl',
            'pump_price',
            'estimated_liters',
            'expected_cost',
            'actual_amount',
            'variance_pct',
            'is_within_tolerance',
            'auto_validated',
            'validation_status',
            'formula_explanation',
        ]);
});
