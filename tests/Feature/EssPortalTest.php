<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->dept = Department::create(['name' => 'Fleet Operations', 'code' => 'OPS']);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-101',
        'first_name' => 'Danilo',
        'last_name' => 'Reyes',
        'email' => 'danilo.reyes@tripease.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Driver',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);

    $this->fuelCat = ClaimCategory::create([
        'code' => 'FUEL-EXP',
        'name' => 'Driver Fuel Expense',
        'type' => 'expense',
        'tax_classification' => 'non_taxable',
        'is_active' => true,
    ]);

    $this->medicalCat = ClaimCategory::create([
        'code' => 'MED-EXP',
        'name' => 'Medical Outpatient',
        'type' => 'medical',
        'tax_classification' => 'de_minimis',
        'is_active' => true,
    ]);

    $this->user = User::factory()->create([
        'name' => 'Danilo Reyes',
        'email' => 'driver1@transport.test',
    ]);

    CompanySetting::setValue('fuel_default_pump_price', 65.00);
    CompanySetting::setValue('fuel_default_efficiency_kpl', 10.00);
    CompanySetting::setValue('fuel_tolerance_percentage', 15.00);
});

test('ess dashboard loads clean interface without HMO, APE, or Life Insurance cards', function () {
    $response = $this->actingAs($this->user)->get(route('ess.dashboard', ['employee_id' => $this->driver->id]));

    $response->assertStatus(200);
    $response->assertSeeText('Employee Self-Service');
    $response->assertSeeText('File Claim & Upload Receipt');
    $response->assertSeeText('Danilo Reyes');
    $response->assertSeeText('Security Bank Account Setup');

    // Asserts zero HMO / wellness clutter
    $response->assertDontSeeText('Digital E-Card');
    $response->assertDontSeeText('Apply for HMO Healthcare Policy');
    $response->assertDontSeeText('Book APE Clinic Appointment');
    $response->assertDontSeeText('Group Life & Disability');
    $response->assertDontSeeText('Request LOA');
});

test('driver can submit fuel claim with receipt upload via ESS', function () {
    $file = UploadedFile::fake()->image('gas_receipt.jpg');

    $response = $this->actingAs($this->user)->post(route('ess.claims.submit'), [
        'employee_id' => $this->driver->id,
        'category_id' => $this->fuelCat->id,
        'type' => 'expense',
        'amount' => 1300.00,
        'receipt_number' => 'OR-GAS-2026-001',
        'merchant_name' => 'Shell Gas Station',
        'expense_date' => now()->toDateString(),
        'description' => 'Gasoline refill during airport dispatch',
        'distance_traveled_km' => 200.0,
        'fuel_pump_price' => 65.0,
        'vehicle_fuel_efficiency_kpl' => 10.0,
        'receipt_file' => $file,
    ]);

    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->driver->id]))
        ->assertSessionHas('status');

    $claim = Claim::where('receipt_number', 'OR-GAS-2026-001')->first();
    expect($claim)->not->toBeNull()
        ->and($claim->employee_id)->toBe($this->driver->id)
        ->and((float) $claim->amount)->toBe(1300.00)
        ->and($claim->approval_status)->toBe('pending_hr')
        ->and($claim->auto_validated)->toBeTrue()
        ->and($claim->attachment_path)->not->toBeNull();

    Storage::disk('public')->assertExists($claim->attachment_path);
});

test('employee can update bank deposit and disbursement details', function () {
    $response = $this->actingAs($this->user)->post(route('ess.bank-details'), [
        'employee_id' => $this->driver->id,
        'payment_method' => 'bank',
        'bank_name' => 'BDO Unibank',
        'bank_account_number' => '1234-5678-9012',
    ]);

    $response->assertRedirect();
    $this->driver->refresh();
    expect($this->driver->payment_method)->toBe('bank')
        ->and($this->driver->bank_name)->toBe('BDO Unibank')
        ->and($this->driver->bank_account_number)->toBe('1234-5678-9012');
});

test('ess displays transparent payslip breakdown when available', function () {
    SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => 'Aug 1-7, 2026',
        'base_pay' => 6000.00,
        'trip_earnings' => 8000.00,
        'performance_bonus' => 1000.00,
        'gross_pay' => 15000.00,
        'sss_deduction' => 600.00,
        'philhealth_deduction' => 200.00,
        'pagibig_deduction' => 100.00,
        'total_deductions' => 900.00,
        'net_pay' => 14100.00,
        'status' => 'released_financial',
    ]);

    $response = $this->actingAs($this->user)->get(route('ess.dashboard', ['employee_id' => $this->driver->id]));

    $response->assertStatus(200);
    $response->assertSeeText('Aug 1-7, 2026');
    $response->assertSeeText('PHP 14,100.00');
});
