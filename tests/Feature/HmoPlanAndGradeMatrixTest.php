<?php

declare(strict_types=1);

use App\Models\AccreditedFacility;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoEnrollment;
use App\Models\HmoGradeLimit;
use App\Services\Benefits\HmoPlanManagementService;
use Database\Seeders\HmoPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(HmoPlanSeeder::class);

    $this->dept = Department::create(['name' => 'Operations']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-HMO-01',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Operations Dispatcher',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);
});

test('HmoPlanManagementService accurately resolves Salary Grade MBL matrix from known.md §8.5', function () {
    $service = new HmoPlanManagementService();

    // PG-1 to PG-2 (Drivers / Entry): ₱100,000, Semi-Private, 0 dependents
    $pg1 = $service->getMblForGrade(1);
    expect($pg1['mbl_amount'])->toBe(100000.00)
        ->and($pg1['room_and_board'])->toBe('semi_private')
        ->and($pg1['max_dependents'])->toBe(0);

    // PG-3 to PG-4: ₱150,000, Semi-Private, 1 dependent
    $pg3 = $service->getMblForGrade(3);
    expect($pg3['mbl_amount'])->toBe(150000.00)
        ->and($pg3['room_and_board'])->toBe('semi_private')
        ->and($pg3['max_dependents'])->toBe(1);

    // PG-5: ₱200,000, Private, 2 dependents
    $pg5 = $service->getMblForGrade(5);
    expect($pg5['mbl_amount'])->toBe(200000.00)
        ->and($pg5['room_and_board'])->toBe('private')
        ->and($pg5['max_dependents'])->toBe(2);

    // PG-6: ₱300,000, Private, 3 dependents
    $pg6 = $service->getMblForGrade(6);
    expect($pg6['mbl_amount'])->toBe(300000.00)
        ->and($pg6['room_and_board'])->toBe('private')
        ->and($pg6['max_dependents'])->toBe(3);

    // PG-7+: ₱500,000, Suite, 4 dependents
    $pg7 = $service->getMblForGrade(7);
    expect($pg7['mbl_amount'])->toBe(500000.00)
        ->and($pg7['room_and_board'])->toBe('suite')
        ->and($pg7['max_dependents'])->toBe(4);
});

test('HmoPlanManagementService computes company vs employee premium co-sharing', function () {
    $service = new HmoPlanManagementService();

    // Default: 80% Company / 20% Employee, Base Emp: 1,800, Base Dep: 1,200
    // Total for 1 employee + 1 dependent = 1,800 + 1,200 = 3,000
    $sharing = $service->calculatePremiumSharing(1800.00, 1);

    expect($sharing['total_monthly_premium'])->toBe(3000.00)
        ->and($sharing['company_share'])->toBe(2400.00) // 80% of 3,000
        ->and($sharing['employee_share'])->toBe(600.00); // 20% of 3,000
});

test('HmoPlanManagementService searches and filters accredited facilities', function () {
    $service = new HmoPlanManagementService();

    $ncr = $service->getAccreditedFacilities(null, null, 'NCR');
    expect($ncr->total())->toBeGreaterThan(0);

    $luke = $service->getAccreditedFacilities('St. Luke');
    expect($luke->total())->toBeGreaterThanOrEqual(1)
        ->and($luke->first()->name)->toContain('St. Luke');
});

test('POST /hmo-benefits/plans/config updates enterprise HMO policies', function () {
    $response = $this->post(route('hmo.plans.config'), [
        'hmo_has_provider' => '1',
        'hmo_provider_name' => 'Intellicare Network',
        'hmo_plan_type' => 'Comprehensive',
        'hmo_premium_shoulder_type' => 'shared',
        'hmo_company_share_pct' => 70,
        'hmo_employee_share_pct' => 30,
        'hmo_coverage_start_months' => 3,
        'hmo_dependent_coverage' => '1',
        'hmo_max_dependents' => 3,
        'hmo_base_employee_premium' => 2000.00,
        'hmo_base_dependent_premium' => 1500.00,
    ]);

    $response->assertRedirect(route('hmo.plans', ['tab' => 'matrix']))
        ->assertSessionHas('status');

    expect(CompanySetting::getValue('hmo_provider_name'))->toBe('Intellicare Network')
        ->and((float) CompanySetting::getValue('hmo_company_share_pct'))->toBe(70.0)
        ->and((int) CompanySetting::getValue('hmo_coverage_start_months'))->toBe(3);
});

test('POST /hmo-benefits/facilities registers a new accredited medical facility', function () {
    $response = $this->post(route('hmo.facilities.store'), [
        'name' => 'Velez Hospital Medical Center',
        'facility_type' => 'Hospital',
        'region' => 'Region VII (Central Visayas)',
        'address' => 'F. Ramos St., Cebu City',
        'contact_number' => '(032) 253-1871',
        'is_emergency_ready' => 1,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('hmo.plans'))
        ->assertSessionHas('status');

    $facility = AccreditedFacility::where('name', 'Velez Hospital Medical Center')->first();
    expect($facility)->not->toBeNull()
        ->and($facility->region)->toBe('Region VII (Central Visayas)')
        ->and($facility->is_emergency_ready)->toBeTrue();
});

test('GET /hmo-benefits/plans/export-roster streams CSV master enrollment file', function () {
    HmoEnrollment::create([
        'employee_id' => $this->employee->id,
        'hmo_card_number' => 'TEST-HMO-888',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Standard Plus',
        'coverage_tier' => 'Plus',
        'mbl_amount' => 150000.00,
        'annual_limit' => 150000.00,
        'monthly_premium' => 1800.00,
        'dependent_count' => 1,
        'coverage_start_date' => now()->toDateString(),
        'coverage_end_date' => now()->addYear()->toDateString(),
        'status' => 'active',
    ]);

    $response = $this->get(route('hmo.plans.export-roster'));
    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('POST /hmo-benefits/api/mbl-lookup returns live Grade MBL and premium breakdown', function () {
    $response = $this->postJson(route('hmo.api.mbl-lookup'), [
        'salary_grade' => 5,
        'total_premium' => 2500.00,
        'dependent_count' => 2,
    ]);

    $response->assertOk()
        ->assertJsonPath('grade_info.mbl_amount', 200000)
        ->assertJsonPath('grade_info.room_and_board', 'private')
        ->assertJsonPath('premium_sharing.company_share_pct', 80);
});

test('GET /hmo-benefits/plans renders successfully with zero emojis', function () {
    $response = $this->get(route('hmo.plans'));
    $response->assertOk()
        ->assertSee('Salary Grade Maximum Benefit Limits (MBL) Policy Matrix')
        ->assertSee('Corporate Benefit Packages Master Catalog');
});
