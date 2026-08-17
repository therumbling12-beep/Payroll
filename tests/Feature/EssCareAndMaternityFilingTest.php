<?php

declare(strict_types=1);

use App\Models\AccidentClaim;
use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->dept = Department::create(['name' => 'Operations']);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-701',
        'first_name' => 'Clarissa',
        'last_name' => 'Montes',
        'email' => 'clarissa.montes@example.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Logistics Specialist',
        'employment_status' => 'regular',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
    ]);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-702',
        'first_name' => 'Rodrigo',
        'last_name' => 'Dela Cruz',
        'email' => 'rodrigo.delacruz@example.com',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 25000.00,
        'daily_rate' => 961.54,
    ]);

    $this->medCategory = ClaimCategory::create([
        'code' => 'MED-PRESCRIPTION',
        'name' => 'Prescription Medicine & Doctor Consultation',
        'type' => 'medical',
        'tax_classification' => 'de_minimis',
        'is_active' => true,
    ]);
});

test('Employee can submit a Medical Aid claim and it respects the PHP 10,000 De Minimis cap', function () {
    CompanySetting::setValue('medical_cash_allowance_annual_cap', 10000.00);

    // Existing medical claim of 7,000 already used
    Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->medCategory->id,
        'type' => 'medical',
        'amount' => 7000.00,
        'non_taxable_amount' => 7000.00,
        'taxable_amount' => 0.00,
        'receipt_number' => 'OR-MED-PREV-01',
        'status' => 'approved',
        'approval_status' => 'approved',
        'expense_date' => '2026-08-01',
        'effective_date' => '2026-08-01',
    ]);

    $file = UploadedFile::fake()->image('medical_receipt.jpg');

    // New claim of 5,000. Remaining cap is 3,000. (3,000 non-taxable, 2,000 taxable)
    $payload = [
        'employee_id' => $this->employee->id,
        'category_id' => $this->medCategory->id,
        'type' => 'medical',
        'amount' => 5000.00,
        'receipt_number' => 'OR-MERC-2026-88',
        'merchant_name' => 'Mercury Drugstore Makati',
        'physician_license_no' => 'PRC-091244',
        'expense_date' => '2026-08-15',
        'description' => 'Prescription maintenance medications',
        'receipt_file' => $file,
    ];

    $response = $this->post(route('ess.claims.submit'), $payload);
    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $claim = Claim::where('receipt_number', 'OR-MERC-2026-88')->first();
    expect($claim)->not->toBeNull()
        ->and((float) $claim->amount)->toBe(5000.00)
        ->and((float) $claim->non_taxable_amount)->toBe(3000.00)
        ->and((float) $claim->taxable_amount)->toBe(2000.00)
        ->and($claim->approval_status)->toBe('pending_hr');

    Storage::disk('public')->assertExists($claim->attachment_path);

    // Verify audit trail logged
    $audit = PayrollAuditTrail::where('action', 'ESS_MEDICAL_CLAIM_SUBMITTED')
        ->where('model_id', $claim->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->user_name)->toBe('Clarissa Montes');
});

test('Female employee can file an RA 11210 Statutory Maternity Benefit Advance via ESS', function () {
    $matNotice = UploadedFile::fake()->create('sss_mat1_notice.pdf', 300);

    $payload = [
        'employee_id' => $this->employee->id,
        'type' => 'maternity',
        'maternity_type' => 'normal_caesarean', // 105 days
        'amount' => 105000.00,
        'receipt_number' => 'MAT-RA11210-EMP701-001',
        'merchant_name' => 'Dr. Victoria Gomez, MD (OB-GYN)',
        'physician_license_no' => 'PRC-0081293',
        'expense_date' => '2026-08-15',
        'description' => '105-Day RA 11210 statutory maternity advance filing with OB-GYN certificate',
        'receipt_file' => $matNotice,
    ];

    $response = $this->post(route('ess.claims.submit'), $payload);
    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $claim = Claim::where('type', 'maternity')->where('employee_id', $this->employee->id)->first();
    expect($claim)->not->toBeNull()
        ->and($claim->maternity_type)->toBe('normal_caesarean')
        ->and($claim->maternity_leave_days)->toBe(105)
        ->and((float) $claim->sss_maternity_share)->toBeGreaterThan(0)
        ->and($claim->approval_status)->toBe('pending_hr');

    Storage::disk('public')->assertExists($claim->attachment_path);
});

test('Driver can file an emergency road accident relief claim via ESS', function () {
    $blotter = UploadedFile::fake()->image('police_blotter.jpg');

    $payload = [
        'employee_id' => $this->driver->id,
        'type' => 'accident',
        'amount' => 8500.00,
        'receipt_number' => 'ACCD-REF-702',
        'incident_date' => '2026-08-15',
        'expense_date' => '2026-08-15',
        'incident_location' => 'EDSA Guadalupe Southbound',
        'hospital_name' => 'Ospital ng Makati ER',
        'description' => 'Minor side collision while in active passenger dispatch',
        'receipt_file' => $blotter,
    ];

    $response = $this->post(route('ess.claims.submit'), $payload);
    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->driver->id]));

    $accident = AccidentClaim::where('employee_id', $this->driver->id)->first();
    expect($accident)->not->toBeNull()
        ->and((float) $accident->bill_amount)->toBe(8500.00)
        ->and($accident->workflow_status)->toBe('pending_hr')
        ->and($accident->documents_uploaded)->toBeTrue();
});
