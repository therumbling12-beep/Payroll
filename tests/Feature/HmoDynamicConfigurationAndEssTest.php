<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoGradeLimit;
use App\Models\PayrollAuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->dept = Department::create(['name' => 'Human Resources']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-303',
        'first_name' => 'Elena',
        'last_name' => 'Santos',
        'email' => 'elena.santos@example.com',
        'department_id' => $this->dept->id,
        'position' => 'HR Specialist',
        'employment_status' => 'regular',
        'monthly_rate' => 38000.00,
        'daily_rate' => 1461.54,
    ]);

    // Seed test HMO grade limits
    HmoGradeLimit::create([
        'grade_min' => 1,
        'grade_max' => 2,
        'title' => 'Basic Care Plan',
        'mbl_amount' => 120000.00,
        'room_and_board' => 'semi_private',
        'max_dependents' => 2,
        'is_active' => true,
    ]);

    HmoGradeLimit::create([
        'grade_min' => 3,
        'grade_max' => 5,
        'title' => 'Plus Health Plan',
        'mbl_amount' => 250000.00,
        'room_and_board' => 'private',
        'max_dependents' => 3,
        'is_active' => true,
    ]);
});

test('ESS dashboard dynamically displays active HMO plan options and MBL limits from database', function () {
    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $response->assertOk();
    $response->assertSee('Basic Care Plan (PHP 120,000.00 MBL - Semi-Private Room)');
    $response->assertSee('Plus Health Plan (PHP 250,000.00 MBL - Private Room)');
});

test('HR Admin can update HMO provider policies, co-sharing, and premium baselines', function () {
    $response = $this->post(route('hmo.plans.config'), [
        'hmo_provider_name' => 'Intellicare (Asalus Corporation)',
        'hmo_plan_type' => 'Comprehensive',
        'hmo_premium_shoulder_type' => 'shared',
        'hmo_company_share_pct' => 90,
        'hmo_employee_share_pct' => 10,
        'hmo_coverage_start_months' => 3,
        'hmo_dependent_coverage' => '1',
        'hmo_max_dependents' => 5,
        'hmo_base_employee_premium' => 2200.00,
        'hmo_base_dependent_premium' => 1500.00,
    ]);

    $response->assertRedirect(route('hmo.plans', ['tab' => 'matrix']));
    $response->assertSessionHas('status');

    expect(CompanySetting::getValue('hmo_provider_name'))->toBe('Intellicare (Asalus Corporation)')
        ->and((float) CompanySetting::getValue('hmo_company_share_pct'))->toBe(90.0)
        ->and((float) CompanySetting::getValue('hmo_base_employee_premium'))->toBe(2200.00)
        ->and((float) CompanySetting::getValue('hmo_base_dependent_premium'))->toBe(1500.00);
});

test('HR Admin can 1-click reset HMO policies to standard factory defaults', function () {
    // Set custom values first
    CompanySetting::setValue('hmo_provider_name', 'Custom HMO Provider Inc');
    CompanySetting::setValue('hmo_company_share_pct', 50.0);
    CompanySetting::setValue('hmo_base_employee_premium', 3500.00);

    $response = $this->post(route('hmo.plans.config.reset'));

    $response->assertRedirect(route('hmo.plans', ['tab' => 'matrix']));
    $response->assertSessionHas('status');

    // Verify factory defaults are restored
    expect(CompanySetting::getValue('hmo_provider_name'))->toBe('Maxicare Healthcare Corporation')
        ->and((float) CompanySetting::getValue('hmo_company_share_pct'))->toBe(80.0)
        ->and((float) CompanySetting::getValue('hmo_employee_share_pct'))->toBe(20.0)
        ->and((float) CompanySetting::getValue('hmo_base_employee_premium'))->toBe(1800.00)
        ->and((float) CompanySetting::getValue('hmo_base_dependent_premium'))->toBe(1200.00);

    // Verify Audit Trail is recorded
    $trail = PayrollAuditTrail::where('action', 'HMO_POLICY_CONFIG_RESET')->first();
    expect($trail)->not->toBeNull();
});

test('HMO Plans page dynamically displays computed MBL range', function () {
    $response = $this->get(route('hmo.plans'));

    $response->assertOk();
    $response->assertSeeText('PHP 120,000 - PHP 250,000');
    $response->assertSeeText('2 Tiered Pay Grade Maximum Entitlements');
});
