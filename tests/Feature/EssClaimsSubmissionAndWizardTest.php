<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->fleetDept = Department::create(['name' => 'Fleet Operations']);
    $this->driver = Employee::create([
        'employee_code' => 'DRV-901',
        'first_name' => 'Marco',
        'last_name' => 'Alcantara',
        'email' => 'marco.alcantara@example.com',
        'department_id' => $this->fleetDept->id,
        'position' => 'Senior TNVS Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
    ]);

    $this->fuelCat = ClaimCategory::create([
        'code' => 'FUEL-EXP',
        'name' => 'Driver Fuel Expense',
        'type' => 'expense',
        'tax_classification' => 'non_taxable',
        'is_active' => true,
    ]);

    $this->tollCat = ClaimCategory::create([
        'code' => 'TOLL-EXP',
        'name' => 'Expressway Toll RFID',
        'type' => 'expense',
        'tax_classification' => 'non_taxable',
        'is_active' => true,
    ]);
});

test('ESS dashboard displays File Claim and Upload Receipt button and active categories', function () {
    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->driver->id]));

    $response->assertOk();
    $response->assertSeeText('File Claim & Upload Receipt');
    $response->assertSeeText('Driver Fuel Expense');
    $response->assertSeeText('Expressway Toll RFID');
});

test('Employee can submit a fuel claim with receipt upload directly from ESS', function () {
    CompanySetting::setValue('fuel_default_pump_price', 65.00);
    CompanySetting::setValue('fuel_default_efficiency_kpl', 10.00);
    CompanySetting::setValue('fuel_tolerance_percentage', 15.00);

    $file = UploadedFile::fake()->image('petron_receipt.jpg');

    $payload = [
        'employee_id' => $this->driver->id,
        'category_id' => $this->fuelCat->id,
        'type' => 'expense',
        'amount' => 1300.00, // 200km / 10kpl * 65 = 1300 exactly (0% variance)
        'receipt_number' => 'OR-PET-2026-991',
        'merchant_name' => 'Petron C5 Southbound',
        'expense_date' => '2026-08-15',
        'description' => 'Driver passenger airport dispatch route',
        'distance_traveled_km' => 200.0,
        'fuel_pump_price' => 65.0,
        'vehicle_fuel_efficiency_kpl' => 10.0,
        'receipt_file' => $file,
    ];

    $response = $this->post(route('ess.claims.submit'), $payload);

    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->driver->id]));
    $response->assertSessionHas('status');

    $claim = Claim::where('receipt_number', 'OR-PET-2026-991')->first();
    expect($claim)->not->toBeNull()
        ->and($claim->employee_id)->toBe($this->driver->id)
        ->and((float) $claim->amount)->toBe(1300.00)
        ->and($claim->status)->toBe('pending')
        ->and($claim->approval_status)->toBe('pending_hr')
        ->and($claim->auto_validated)->toBeTrue()
        ->and((float) $claim->fuel_variance_pct)->toBe(0.0)
        ->and($claim->attachment_path)->not->toBeNull();

    Storage::disk('public')->assertExists($claim->attachment_path);

    // Verify it is visible live on the ESS portal
    $essResponse = $this->get(route('ess.dashboard', ['employee_id' => $this->driver->id]));
    $essResponse->assertSee('OR-PET-2026-991');
    $essResponse->assertSeeText('Submitted');

    // Verify it is also immediately visible live on the HR Admin Expenses page
    $hrResponse = $this->get(route('claims.expenses'));
    $hrResponse->assertOk();
    $hrResponse->assertSee('OR-PET-2026-991');
});

test('Employee can submit an operational expense claim from ESS and it classifies non-taxable', function () {
    $file = UploadedFile::fake()->create('toll_rfid_statement.pdf', 150);

    $payload = [
        'employee_id' => $this->driver->id,
        'category_id' => $this->tollCat->id,
        'type' => 'expense',
        'amount' => 500.00,
        'receipt_number' => 'OR-TOLL-552',
        'merchant_name' => 'AutoSweep RFID',
        'expense_date' => '2026-08-15',
        'description' => 'Skyway Stage 3 dispatch transit',
        'receipt_file' => $file,
    ];

    $response = $this->post(route('ess.claims.submit'), $payload);

    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->driver->id]));

    $claim = Claim::where('receipt_number', 'OR-TOLL-552')->first();
    expect($claim)->not->toBeNull()
        ->and((float) $claim->non_taxable_amount)->toBe(500.00)
        ->and((float) $claim->taxable_amount)->toBe(0.00)
        ->and($claim->approval_status)->toBe('pending_hr');

    Storage::disk('public')->assertExists($claim->attachment_path);
});

test('Fuel claim exceeding variance tolerance is flagged for HR review', function () {
    CompanySetting::setValue('fuel_default_pump_price', 65.00);
    CompanySetting::setValue('fuel_default_efficiency_kpl', 10.00);
    CompanySetting::setValue('fuel_tolerance_percentage', 15.00);

    $file = UploadedFile::fake()->image('fuel_high_variance.jpg');

    $payload = [
        'employee_id' => $this->driver->id,
        'category_id' => $this->fuelCat->id,
        'type' => 'expense',
        'amount' => 2000.00, // Expected is 100km / 10kpl * 65 = 650. 2000 is +207.7% variance
        'receipt_number' => 'OR-FUEL-VAR-99',
        'merchant_name' => 'Shell Expressway',
        'expense_date' => '2026-08-15',
        'distance_traveled_km' => 100.0,
        'fuel_pump_price' => 65.0,
        'vehicle_fuel_efficiency_kpl' => 10.0,
        'receipt_file' => $file,
    ];

    $response = $this->post(route('ess.claims.submit'), $payload);
    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->driver->id]));

    $claim = Claim::where('receipt_number', 'OR-FUEL-VAR-99')->first();
    expect($claim)->not->toBeNull()
        ->and($claim->auto_validated)->toBeFalse()
        ->and((float) $claim->fuel_variance_pct)->toBeGreaterThan(15.0);
});

test('Claim submission fails validation when required fields or valid file formats are missing', function () {
    $invalidFile = UploadedFile::fake()->create('malicious.exe', 500);

    $payload = [
        'employee_id' => $this->driver->id,
        'category_id' => $this->fuelCat->id,
        'type' => 'expense',
        'amount' => -100.00, // Negative amount not allowed
        'receipt_number' => '', // Required
        'expense_date' => 'not-a-date',
        'receipt_file' => $invalidFile,
    ];

    $response = $this->post(route('ess.claims.submit'), $payload);
    $response->assertSessionHasErrors(['amount', 'receipt_number', 'expense_date', 'receipt_file']);
});

test('Claim submission logs an ESS_CLAIM_SUBMITTED audit trail entry', function () {
    $payload = [
        'employee_id' => $this->driver->id,
        'category_id' => $this->tollCat->id,
        'type' => 'expense',
        'amount' => 350.00,
        'receipt_number' => 'OR-AUDIT-TEST-1',
        'merchant_name' => 'Calax Tollways',
        'expense_date' => '2026-08-15',
    ];

    $response = $this->post(route('ess.claims.submit'), $payload);
    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->driver->id]));

    $claim = Claim::where('receipt_number', 'OR-AUDIT-TEST-1')->first();
    expect($claim)->not->toBeNull();

    $audit = \App\Models\PayrollAuditTrail::where('action', 'ESS_CLAIM_SUBMITTED')
        ->where('model_id', $claim->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->user_name)->toBe('Marco Alcantara');
});

