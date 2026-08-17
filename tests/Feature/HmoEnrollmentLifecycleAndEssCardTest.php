<?php

declare(strict_types=1);

use App\Models\BudgetRequisition;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoDependent;
use App\Models\HmoEnrollment;
use App\Services\Benefits\HmoEnrollmentService;
use App\Services\Benefits\HmoPlanManagementService;
use Database\Seeders\HmoPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(HmoPlanSeeder::class);
    Storage::fake('public');

    $this->dept = Department::create(['name' => 'Fleet Operations']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-ESS-01',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripease.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Operations Specialist',
        'monthly_rate' => 38000.00,
        'daily_rate' => 1461.54,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);
});

test('Step 1: Employee applies for HMO enrollment via ESS with dependents and documents', function () {
    $idPhoto = UploadedFile::fake()->image('id_photo.jpg');
    $marriageCert = UploadedFile::fake()->create('marriage_cert.pdf', 100);
    $childCert = UploadedFile::fake()->create('birth_cert_child.pdf', 100);

    $response = $this->post(route('ess.hmo.apply'), [
        'employee_id' => $this->employee->id,
        'coverage_tier' => 'Plus',
        'id_photo' => $idPhoto,
        'marriage_cert' => $marriageCert,
        'dependents' => [
            [
                'full_name' => 'Carlos Santos',
                'relationship' => 'Spouse',
                'birth_date' => '1992-05-14',
                'gender' => 'Male',
            ],
            [
                'full_name' => 'Leo Santos',
                'relationship' => 'Child',
                'birth_date' => '2020-11-20',
                'gender' => 'Male',
                'birth_cert' => $childCert,
            ],
        ],
        'notes' => 'ESS Application for spouse and child.',
    ]);

    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->employee->id]))
        ->assertSessionHas('status');

    $enrollment = HmoEnrollment::where('employee_id', $this->employee->id)->first();
    expect($enrollment)->not->toBeNull()
        ->and($enrollment->enrollment_status)->toBe('submitted')
        ->and($enrollment->coverage_tier)->toBe('Plus')
        ->and($enrollment->dependent_count)->toBe(2)
        ->and($enrollment->dependents)->toHaveCount(2)
        ->and($enrollment->id_photo_path)->not->toBeNull();

    $childDep = HmoDependent::where('full_name', 'Leo Santos')->first();
    expect($childDep)->not->toBeNull()
        ->and($childDep->status)->toBe('pending')
        ->and($childDep->birth_cert_path)->not->toBeNull();
});

test('Step 2: HR Team 4 validates eligibility and approves application', function () {
    $service = app(HmoEnrollmentService::class);
    $enrollment = $service->submitApplication($this->employee->id, [
        'coverage_tier' => 'Plus',
        'annual_limit' => 150000.00,
    ]);

    $response = $this->post(route('hmo.enrollments.hr-validate', $enrollment), [
        'remarks' => 'Employee regularized > 1 year. Documents verified.',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'approvals']))
        ->assertSessionHas('status');

    $enrollment->refresh();
    expect($enrollment->enrollment_status)->toBe('hr_approved')
        ->and($enrollment->hr_reviewed_at)->not->toBeNull()
        ->and($enrollment->hr_remarks)->toContain('Documents verified');
});

test('Step 3: HR requests budget allocation linking to Team 5 Finance', function () {
    $service = app(HmoEnrollmentService::class);
    $enrollment = $service->submitApplication($this->employee->id, [
        'coverage_tier' => 'Plus',
        'annual_limit' => 150000.00,
    ]);
    $service->validateApplicationByHr($enrollment);

    $response = $this->post(route('hmo.enrollments.request-budget', $enrollment));

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'approvals']))
        ->assertSessionHas('status');

    $enrollment->refresh();
    expect($enrollment->enrollment_status)->toBe('budget_requested')
        ->and($enrollment->budget_requisition_id)->not->toBeNull();

    $budgetReq = BudgetRequisition::find($enrollment->budget_requisition_id);
    expect($budgetReq)->not->toBeNull()
        ->and($budgetReq->category)->toBe('HMO Healthcare Coverage')
        ->and((float) $budgetReq->amount)->toBeGreaterThan(0);
});

test('Step 4: Provider enrollment finalization activates coverage and issues official card', function () {
    $service = app(HmoEnrollmentService::class);
    $enrollment = $service->submitApplication($this->employee->id, [
        'coverage_tier' => 'Plus',
        'annual_limit' => 150000.00,
    ], null, null, [
        ['full_name' => 'Carlos Santos', 'relationship' => 'Spouse'],
    ]);
    $service->validateApplicationByHr($enrollment);
    $service->requestBudgetForEnrollment($enrollment);

    $response = $this->post(route('hmo.enrollments.activate', $enrollment), [
        'hmo_card_number' => 'MAX-2026-888999',
        'provider_plan' => 'Maxicare Prime Care',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']))
        ->assertSessionHas('status');

    $enrollment->refresh();
    expect($enrollment->enrollment_status)->toBe('active')
        ->and($enrollment->status)->toBe('active')
        ->and($enrollment->hmo_card_number)->toBe('MAX-2026-888999');

    $dependent = $enrollment->dependents->first();
    expect($dependent->status)->toBe('verified');
});

test('Step 5: ESS Digital HMO Card API returns verified card payload with QR and accredited ER facilities', function () {
    $service = app(HmoEnrollmentService::class);
    $enrollment = $service->submitApplication($this->employee->id, [
        'coverage_tier' => 'Plus',
        'annual_limit' => 150000.00,
    ]);
    $service->finalizeProviderEnrollment($enrollment, 'MAX-2026-777111');

    $response = $this->getJson(route('ess.hmo.card', ['employee_id' => $this->employee->id]));

    $response->assertOk()
        ->assertJsonPath('card_number', 'MAX-2026-777111')
        ->assertJsonPath('employee_name', 'Maria Santos')
        ->assertJsonPath('mbl_limit', 150000)
        ->assertJsonPath('mbl_remaining', 150000)
        ->assertJsonStructure([
            'card_number',
            'provider_name',
            'plan_tier',
            'employee_name',
            'mbl_limit',
            'mbl_remaining',
            'qr_payload',
            'emergency_facilities',
        ]);
});

test('Step 6: Annual renewal extends coverage by 1 year', function () {
    $service = app(HmoEnrollmentService::class);
    $enrollment = $service->submitApplication($this->employee->id, [
        'coverage_tier' => 'Basic',
        'coverage_start_date' => '2025-01-01',
        'coverage_end_date' => '2026-01-01',
    ]);
    $service->finalizeProviderEnrollment($enrollment, 'MAX-2025-111222');

    $response = $this->post(route('hmo.enrollments.renew', $enrollment));

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']))
        ->assertSessionHas('status');

    $enrollment->refresh();
    expect($enrollment->coverage_end_date->format('Y-m-d'))->toBe('2027-01-01')
        ->and($enrollment->renewed_at)->not->toBeNull()
        ->and($enrollment->enrollment_status)->toBe('active');
});

test('Rejection flow records reason and sets status to rejected', function () {
    $service = app(HmoEnrollmentService::class);
    $enrollment = $service->submitApplication($this->employee->id, [
        'coverage_tier' => 'Plus',
    ]);

    $response = $this->post(route('hmo.enrollments.reject', $enrollment), [
        'rejection_reason' => 'Probationary period requirement not met.',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'approvals']))
        ->assertSessionHas('status');

    $enrollment->refresh();
    expect($enrollment->enrollment_status)->toBe('rejected')
        ->and($enrollment->status)->toBe('inactive')
        ->and($enrollment->rejection_reason)->toBe('Probationary period requirement not met.');
});

test('Phase 1: isLowBalance and utilizationPercentage return accurate metrics', function () {
    $enrollment = HmoEnrollment::create([
        'employee_id' => $this->employee->id,
        'hmo_card_number' => 'MED-TEST-LOW',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Maxicare Plus',
        'annual_limit' => 100000.00,
        'mbl_amount' => 100000.00,
        'monthly_premium' => 1800.00,
        'status' => 'active',
    ]);

    expect($enrollment->isLowBalance())->toBeFalse()
        ->and($enrollment->utilizationPercentage())->toBe(0.0);

    // Add utilization log of 85,000 (85% used, 15,000 remaining, which is < 20%)
    \App\Models\HmoUtilizationLog::create([
        'hmo_enrollment_id' => $enrollment->id,
        'employee_id' => $this->employee->id,
        'benefit_type' => 'HMO — Hospitalization',
        'service_provider' => 'St. Luke Medical Center',
        'utilized_at' => now()->toDateString(),
        'utilized_amount' => 85000.00,
        'remaining_balance' => 15000.00,
    ]);

    expect($enrollment->isLowBalance())->toBeTrue()
        ->and($enrollment->utilizationPercentage())->toBe(85.0);
});

test('Phase 1: ESS dashboard renders open enrollment banner and low balance warning', function () {
    $service = app(HmoEnrollmentService::class);
    $enrollment = $service->submitApplication($this->employee->id, [
        'coverage_tier' => 'Plus',
        'annual_limit' => 100000.00,
    ]);
    $service->finalizeProviderEnrollment($enrollment, 'MAX-2026-ESS-WARN');

    // Add utilization to trigger low balance
    \App\Models\HmoUtilizationLog::create([
        'hmo_enrollment_id' => $enrollment->id,
        'employee_id' => $this->employee->id,
        'benefit_type' => 'HMO — Emergency',
        'service_provider' => 'Makati Medical Center',
        'utilized_at' => now()->toDateString(),
        'utilized_amount' => 85000.00,
        'remaining_balance' => 15000.00,
    ]);

    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $response->assertOk()
        ->assertSee('Annual Open Enrollment Active')
        ->assertSee('Notice: Remaining MBL is below 20%')
        ->assertSee('Apply or Add Family');
});

test('Phase 3: Open enrollment banner clearly displays readable date window and seeder runs cleanly', function () {
    $this->seed(\Database\Seeders\HmoLiveDemoSeeder::class);

    $liza = Employee::where('employee_code', 'EMP-DEMO-01')->first();
    expect($liza)->not->toBeNull();

    $response = $this->get(route('ess.dashboard', ['employee_id' => $liza->id]));

    $response->assertOk()
        ->assertSee('Annual Open Enrollment Active')
        ->assertSee('Sign-up is open from')
        ->assertSee('Notice: Remaining MBL is below 20%')
        ->assertSee('Liza Cruz');
});
