<?php

declare(strict_types=1);

use App\Models\AccidentClaim;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\DriverPoolLedger;
use App\Models\Employee;
use App\Services\Benefits\DriverInsurancePoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->dept = Department::create(['name' => 'Fleet Operations']);
    $this->driver = Employee::create([
        'employee_code' => 'DRV-001',
        'first_name' => 'Danilo',
        'last_name' => 'Reyes',
        'email' => 'danilo.reyes@tripease.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior TNVS Driver',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);

    CompanySetting::setValue('driver_benefit_contribution_rate', '0.03');
    CompanySetting::setValue('driver_pool_company_match_pct', '50.0');
});

test('Driver insurance standalone index page renders successfully with statistics', function () {
    $response = $this->get(route('driver-insurance.index'));

    $response->assertOk()
        ->assertSee('Driver Accident Insurance Pool')
        ->assertSee('Danilo Reyes')
        ->assertSee('Driver 3% Deductions');
});

test('Step 1: Driver files accident claim with evidence documents', function () {
    $policeReport = UploadedFile::fake()->create('police_blotter.pdf', 150);
    $medicalReceipt = UploadedFile::fake()->image('medical_or.jpg');
    $damagePhoto = UploadedFile::fake()->image('bumper_damage.jpg');

    $response = $this->post(route('driver-insurance.file-claim'), [
        'employee_id' => $this->driver->id,
        'incident_type' => 'Work Injury',
        'incident_date' => now()->subDays(2)->toDateString(),
        'vehicle_plate_number' => 'NBD-9081',
        'trip_id' => 'TRIP-2026-88124',
        'bill_amount' => 12500.00,
        'diagnosis' => 'Right arm contusion and laceration',
        'description' => 'Minor collision during active passenger trip on EDSA.',
        'police_report' => $policeReport,
        'medical_receipt' => $medicalReceipt,
        'incident_photo' => $damagePhoto,
    ]);

    $response->assertRedirect(route('driver-insurance.index'))
        ->assertSessionHas('status');

    $claim = AccidentClaim::where('employee_id', $this->driver->id)->first();
    expect($claim)->not->toBeNull()
        ->and($claim->workflow_status)->toBe('pending_hr')
        ->and($claim->hr_status)->toBe('pending')
        ->and($claim->vehicle_plate_number)->toBe('NBD-9081')
        ->and((float) $claim->bill_amount)->toBe(12500.00)
        ->and($claim->police_report_path)->not->toBeNull()
        ->and($claim->medical_receipt_path)->not->toBeNull()
        ->and($claim->incident_photo_path)->not->toBeNull()
        ->and($claim->documents_uploaded)->toBeTrue();
});

test('Step 2: HR Team 4 validates claim and sets approved amount ceiling', function () {
    $service = app(DriverInsurancePoolService::class);
    $claim = $service->fileClaim($this->driver->id, [
        'incident_type' => 'Work Injury',
        'incident_date' => now()->subDays(1)->toDateString(),
        'bill_amount' => 15000.00,
        'description' => 'Road accident injury',
    ]);

    $response = $this->post(route('driver-insurance.claim.approve-hr', $claim), [
        'approved_amount' => 14000.00,
        'remarks' => 'Verified active trip status and medical receipts.',
    ]);

    $response->assertRedirect(route('driver-insurance.index'))
        ->assertSessionHas('status');

    $claim->refresh();
    expect($claim->workflow_status)->toBe('pending_admin')
        ->and($claim->hr_status)->toBe('approved')
        ->and((float) $claim->approved_amount)->toBe(14000.00)
        ->and($claim->hr_reviewed_at)->not->toBeNull();
});

test('Step 3: Fleet Admin reviews vehicle assessment and clears claim', function () {
    $service = app(DriverInsurancePoolService::class);
    $claim = $service->fileClaim($this->driver->id, [
        'incident_type' => 'Work Injury',
        'incident_date' => now()->toDateString(),
        'bill_amount' => 8000.00,
        'description' => 'Minor bumper incident',
    ]);
    $service->approveHr($claim, 8000.00, 'HR Verified');

    $response = $this->post(route('driver-insurance.claim.approve-admin', $claim), [
        'remarks' => 'Vehicle inspection and police blotter verified.',
    ]);

    $response->assertRedirect(route('driver-insurance.index'))
        ->assertSessionHas('status');

    $claim->refresh();
    expect($claim->workflow_status)->toBe('pending_finance')
        ->and($claim->admin_status)->toBe('approved')
        ->and($claim->admin_reviewed_at)->not->toBeNull();
});

test('Step 4: Finance Team 5 authorizes disbursement and updates Pool Ledger', function () {
    $service = app(DriverInsurancePoolService::class);
    $claim = $service->fileClaim($this->driver->id, [
        'incident_type' => 'Work Injury',
        'incident_date' => now()->toDateString(),
        'bill_amount' => 9500.00,
        'description' => 'Dislocated finger on duty',
    ]);
    $service->approveHr($claim, 9500.00, 'HR Verified');
    $service->approveAdmin($claim, 'Admin Cleared');

    $response = $this->post(route('driver-insurance.claim.approve-finance', $claim), [
        'remarks' => 'Disbursement authorized from Driver Pool.',
    ]);

    $response->assertRedirect(route('driver-insurance.index'))
        ->assertSessionHas('status');

    $claim->refresh();
    expect($claim->workflow_status)->toBe('approved')
        ->and($claim->finance_status)->toBe('approved')
        ->and($claim->status)->toBe('paid');

    $ledgerEntry = DriverPoolLedger::where('reference_code', $claim->incident_number)->first();
    expect($ledgerEntry)->not->toBeNull()
        ->and($ledgerEntry->entry_type)->toBe('claim_disbursement')
        ->and((float) $ledgerEntry->amount)->toBe(-9500.00);
});

test('Return claim records return reason and moves status to returned', function () {
    $service = app(DriverInsurancePoolService::class);
    $claim = $service->fileClaim($this->driver->id, [
        'incident_type' => 'Work Injury',
        'incident_date' => now()->toDateString(),
        'bill_amount' => 5000.00,
        'description' => 'Missing receipt sample',
    ]);

    $response = $this->post(route('driver-insurance.claim.return', $claim), [
        'remarks' => 'Please attach official BIR receipts with clinic TIN number.',
    ]);

    $response->assertRedirect(route('driver-insurance.index'))
        ->assertSessionHas('status');

    $claim->refresh();
    expect($claim->workflow_status)->toBe('returned')
        ->and($claim->return_reason)->toBe('Please attach official BIR receipts with clinic TIN number.')
        ->and($claim->returned_at)->not->toBeNull();
});

test('Driver Pool contribution rate and company match settings can be updated', function () {
    $response = $this->post(route('driver-insurance.update-contribution-rate'), [
        'contribution_rate' => 3.5,
        'company_match_pct' => 75.0,
    ]);

    $response->assertRedirect(route('driver-insurance.index'))
        ->assertSessionHas('status');

    $rate = (float) CompanySetting::getValue('driver_benefit_contribution_rate');
    $match = (float) CompanySetting::getValue('driver_pool_company_match_pct');

    expect($rate)->toBe(0.035)
        ->and($match)->toBe(75.0);
});

test('Export Pool Ledger CSV returns streamed CSV with accurate headers', function () {
    DriverPoolLedger::create([
        'employee_id' => $this->driver->id,
        'entry_type' => 'driver_contribution',
        'amount' => 840.00,
        'running_balance' => 840.00,
        'reference_code' => 'PAY-2026-08-1',
        'description' => 'Payroll deduction for Danilo Reyes',
    ]);

    $response = $this->get(route('driver-insurance.export-ledger'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
