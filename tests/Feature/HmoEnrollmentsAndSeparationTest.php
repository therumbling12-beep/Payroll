<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoDependent;
use App\Models\HmoEnrollment;
use App\Models\HmoUtilizationLog;
use App\Services\Benefits\HmoEnrollmentService;
use App\Services\Benefits\HmoPlanManagementService;
use Database\Seeders\HmoPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(HmoPlanSeeder::class);

    $this->dept = Department::create(['name' => 'Corporate Engineering']);

    $this->regularEmp = Employee::create([
        'employee_code' => 'EMP-REG-01',
        'first_name' => 'Lorenzo',
        'last_name' => 'Valdez',
        'email' => 'lorenzo.valdez@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Platform Architect',
        'employment_status' => 'regular',
        'monthly_rate' => 60000.00,
        'hire_date' => now()->subYears(2),
    ]);

    $this->probationaryEmp = Employee::create([
        'employee_code' => 'EMP-PROB-02',
        'first_name' => 'Camille',
        'last_name' => 'Mendoza',
        'email' => 'camille.mendoza@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Junior QA Analyst',
        'employment_status' => 'probationary',
        'monthly_rate' => 25000.00,
        'hire_date' => now()->subMonths(2),
    ]);

    $this->driverEmp = Employee::create([
        'employee_code' => 'EMP-DRV-03',
        'first_name' => 'Nestor',
        'last_name' => 'Dizon',
        'email' => 'nestor.dizon@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'TNVS Driver Partner',
        'employment_status' => 'regular',
        'monthly_rate' => 30000.00,
        'hire_date' => now()->subYear(),
    ]);
});

test('GET /hmo-benefits/enrollments renders workforce roster and eligibility tabs', function () {
    $response = $this->get(route('hmo.enrollments'));

    $response->assertOk()
        ->assertSee('Employee HMO Enrollments')
        ->assertSee('Workforce Healthcare Roster')
        ->assertSee('Applications Queue');
});

test('Automated Eligibility Engine evaluates regular, probationary, and driver staff', function () {
    $service = app(HmoEnrollmentService::class);

    $regularStatus = $service->getEligibilityStatus($this->regularEmp);
    expect($regularStatus['status'])->toBe('eligible')
        ->and($regularStatus['eligible_plan'])->toBe('Premium Plus');

    $probStatus = $service->getEligibilityStatus($this->probationaryEmp);
    expect($probStatus['status'])->toBe('probationary_pending');

    $driverStatus = $service->getEligibilityStatus($this->driverEmp);
    expect($driverStatus['status'])->toBe('driver_pool')
        ->and($driverStatus['eligible_plan'])->toBe('Driver Fleet Care');
});

test('POST /hmo-benefits/plans/enroll enrolls employee into HMO coverage roster', function () {
    $response = $this->post(route('hmo.enroll'), [
        'employee_id' => $this->regularEmp->id,
        'hmo_provider' => 'Maxicare Healthcare',
        'provider_plan' => 'Maxicare Premium Gold',
        'coverage_tier' => 'Premium',
        'annual_limit' => 200000.00,
        'monthly_premium' => 2400.00,
        'dependent_count' => 1,
        'coverage_start_date' => now()->format('Y-m-d'),
        'coverage_end_date' => now()->addYear()->format('Y-m-d'),
        'notes' => 'Executive regular employee enrollment.',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('hmo_enrollments', [
        'employee_id' => $this->regularEmp->id,
        'hmo_provider' => 'Maxicare Healthcare',
        'coverage_tier' => 'Premium',
        'annual_limit' => 200000.00,
        'monthly_premium' => 2400.00,
        'status' => 'active',
    ]);
});

test('POST /hmo-benefits/enrollments/sync-payroll syncs employee HMO contribution shares to Payroll', function () {
    HmoEnrollment::create([
        'employee_id' => $this->regularEmp->id,
        'hmo_card_number' => 'SYNC-TEST-001',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Corporate Plan',
        'coverage_tier' => 'Premium',
        'annual_limit' => 200000.00,
        'mbl_amount' => 200000.00,
        'monthly_premium' => 2000.00,
        'dependent_count' => 0,
        'coverage_start_date' => now()->toDateString(),
        'coverage_end_date' => now()->addYear()->toDateString(),
        'status' => 'active',
    ]);

    $response = $this->post(route('hmo.enrollments.sync-payroll'));

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']))
        ->assertSessionHas('status');
});

test('POST /hmo-benefits/plans/log-utilization records medical claim and decrements remaining MBL', function () {
    $enrollment = HmoEnrollment::create([
        'employee_id' => $this->regularEmp->id,
        'hmo_card_number' => 'MED-UTIL-777',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Corporate Plan',
        'coverage_tier' => 'Plus',
        'annual_limit' => 150000.00,
        'mbl_amount' => 150000.00,
        'monthly_premium' => 1800.00,
        'dependent_count' => 0,
        'coverage_start_date' => now()->toDateString(),
        'coverage_end_date' => now()->addYear()->toDateString(),
        'status' => 'active',
    ]);

    $response = $this->post(route('hmo.log-utilization'), [
        'hmo_enrollment_id' => $enrollment->id,
        'service_type' => 'Outpatient Consultation',
        'hospital_clinic_name' => 'The Medical City Ortigas',
        'utilized_amount' => 3500.00,
        'service_date' => now()->format('Y-m-d'),
        'diagnosis' => 'Acute viral upper respiratory infection',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('hmo_utilization_logs', [
        'hmo_enrollment_id' => $enrollment->id,
        'service_provider' => 'The Medical City Ortigas',
        'utilized_amount' => 3500.00,
    ]);

    expect((float) $enrollment->totalUtilized())->toBe(3500.00)
        ->and((float) $enrollment->remainingBalance())->toBe(146500.00);
});

test('POST /hmo-benefits/enrollments/{enrollment}/deactivate halts coverage and deactivates dependents on separation', function () {
    $enrollment = HmoEnrollment::create([
        'employee_id' => $this->regularEmp->id,
        'hmo_card_number' => 'SEP-TEST-002',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Corporate Plan',
        'coverage_tier' => 'Premium',
        'annual_limit' => 200000.00,
        'mbl_amount' => 200000.00,
        'monthly_premium' => 2000.00,
        'dependent_count' => 1,
        'coverage_start_date' => now()->subMonths(6)->toDateString(),
        'coverage_end_date' => now()->addMonths(6)->toDateString(),
        'status' => 'active',
    ]);

    $dep = HmoDependent::create([
        'hmo_enrollment_id' => $enrollment->id,
        'employee_id' => $this->regularEmp->id,
        'full_name' => 'Maria Valdez',
        'relationship' => 'Spouse',
        'status' => 'verified',
    ]);

    $response = $this->post(route('hmo.enrollments.deactivate', $enrollment), [
        'separation_reason' => 'Voluntary Resignation',
    ]);

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']))
        ->assertSessionHas('status');

    $enrollment->refresh();
    $dep->refresh();

    expect($enrollment->status)->toBe('inactive')
        ->and($enrollment->enrollment_status)->toBe('cancelled')
        ->and($dep->status)->toBe('inactive');
});

test('POST /hmo-benefits/enrollments/{enrollment}/renew extends policy for 1 year', function () {
    $enrollment = HmoEnrollment::create([
        'employee_id' => $this->regularEmp->id,
        'hmo_card_number' => 'RENEW-TEST-003',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Corporate Plan',
        'coverage_tier' => 'Premium',
        'annual_limit' => 200000.00,
        'mbl_amount' => 200000.00,
        'monthly_premium' => 2000.00,
        'dependent_count' => 0,
        'coverage_start_date' => now()->subYear()->toDateString(),
        'coverage_end_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $response = $this->post(route('hmo.enrollments.renew', $enrollment));

    $response->assertRedirect(route('hmo.enrollments', ['tab' => 'roster']))
        ->assertSessionHas('status');

    $enrollment->refresh();
    expect($enrollment->coverage_end_date?->format('Y-m-d'))->toBe(now()->addYear()->format('Y-m-d'));
});

test('Phase 1: GET /hmo-benefits/enrollments with quick filters and low balance badges', function () {
    $enrollment = HmoEnrollment::create([
        'employee_id' => $this->regularEmp->id,
        'hmo_card_number' => 'ROSTER-WARN-001',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Corporate Plan',
        'coverage_tier' => 'Premium',
        'annual_limit' => 200000.00,
        'mbl_amount' => 200000.00,
        'monthly_premium' => 2000.00,
        'dependent_count' => 0,
        'coverage_start_date' => now()->toDateString(),
        'coverage_end_date' => now()->addMonths(6)->toDateString(),
        'status' => 'active',
    ]);

    // High utilization to trigger Low Balance badge
    \App\Models\HmoUtilizationLog::create([
        'hmo_enrollment_id' => $enrollment->id,
        'employee_id' => $this->regularEmp->id,
        'benefit_type' => 'HMO — Hospitalization',
        'service_provider' => 'Asian Hospital',
        'utilized_at' => now()->toDateString(),
        'utilized_amount' => 170000.00,
        'remaining_balance' => 30000.00,
    ]);

    $response = $this->get(route('hmo.enrollments', ['tab' => 'roster', 'filter' => 'all']));

    $response->assertOk()
        ->assertSee('All Employees')
        ->assertSee('Drivers')
        ->assertSee('Office Staff')
        ->assertSee('Expiring Soon')
        ->assertSee('Low Balance (&lt; 20%)', false);
});
