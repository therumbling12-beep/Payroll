<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->dept = Department::create(['name' => 'Field Operations']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-501',
        'first_name' => 'Clara',
        'last_name' => 'Mendoza',
        'email' => 'clara.mendoza@example.com',
        'department_id' => $this->dept->id,
        'position' => 'Logistics Dispatcher',
        'employment_status' => 'regular',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
    ]);

    $this->category = ClaimCategory::create([
        'code' => 'OPS-MEAL',
        'name' => 'Overtime Meal Allowance',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'is_active' => true,
    ]);
});

test('Admin can reset claim policies to standard company defaults', function () {
    // Set custom/modified values first
    CompanySetting::setValue('fuel_default_pump_price', 99.00);
    CompanySetting::setValue('fuel_tolerance_percentage', 35.00);
    CompanySetting::setValue('performance_bonus_multiplier', 3000.00);
    CompanySetting::setValue('medical_de_minimis_annual_cap', 25000.00);

    $response = $this->post(route('claims.settings.reset'));

    $response->assertRedirect();
    $response->assertSessionHas('status');

    // Verify all values are restored to standard company defaults
    expect((float) CompanySetting::getValue('fuel_default_pump_price'))->toBe(65.00)
        ->and((float) CompanySetting::getValue('fuel_default_efficiency_kpl'))->toBe(10.00)
        ->and((float) CompanySetting::getValue('fuel_tolerance_percentage'))->toBe(15.00)
        ->and((float) CompanySetting::getValue('performance_bonus_multiplier'))->toBe(1500.00)
        ->and((float) CompanySetting::getValue('driver_consistency_bonus'))->toBe(500.00)
        ->and((float) CompanySetting::getValue('medical_de_minimis_annual_cap'))->toBe(10000.00)
        ->and((float) CompanySetting::getValue('sss_max_msc'))->toBe(35000.00);

    // Verify Audit Trail is recorded
    $trail = PayrollAuditTrail::where('action', 'CLAIM_POLICY_SETTINGS_RESET')->first();
    expect($trail)->not->toBeNull();
});

test('ESS dashboard displays delivery-style 4-step progress tracker and plain-English timing', function () {
    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'amount' => 450.00,
        'receipt_number' => 'OR-MEAL-889',
        'status' => 'pending',
        'approval_status' => 'pending_hr',
        'description' => 'Late shift passenger dispatch overtime meal',
        'attachment_path' => 'attachments/sample_meal_receipt.jpg',
    ]);

    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $response->assertOk();
    $response->assertSeeText('1. Submitted');
    $response->assertSeeText('2. HR Review');
    $response->assertSeeText('3. Finance OK');
    $response->assertSeeText('4. In Payslip');
    $response->assertSeeText('Waiting for HR verification (Estimated review: 1-2 business days)');
    $response->assertSeeText('View Receipt');
});
