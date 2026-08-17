<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Claims\ClaimCategoryManagementService;
use App\Services\Claims\ClaimTaxabilityService;
use Database\Seeders\ClaimCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ClaimCategorySeeder::class);

    $this->fleetDept = Department::create(['name' => 'Fleet Operations']);
    $this->adminDept = Department::create(['name' => 'Administration & HR']);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-TAX-01',
        'first_name' => 'Eduardo',
        'last_name' => 'Ramos',
        'email' => 'eduardo.ramos@tripwise.com',
        'department_id' => $this->fleetDept->id,
        'position' => 'TNVS Senior Driver',
        'monthly_rate' => 35000.00,
        'daily_rate' => 1346.15,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    $this->staff = Employee::create([
        'employee_code' => 'STF-TAX-02',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripwise.com',
        'department_id' => $this->adminDept->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(3),
    ]);
});

test('ClaimCategoryManagementService creates and toggles categories with tax classification', function () {
    $service = new ClaimCategoryManagementService();

    $category = $service->createCategory([
        'name' => 'Emergency Tool Kit Reimbursement',
        'code' => 'CAT-TOOL-01',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'max_amount' => 3500.00,
        'applicable_to' => 'driver',
        'description' => 'Reimbursement for emergency roadside repair tools.',
    ]);

    expect($category)->toBeInstanceOf(ClaimCategory::class)
        ->and($category->code)->toBe('CAT-TOOL-01')
        ->and($category->tax_classification)->toBe('non_taxable')
        ->and($category->isNonTaxable())->toBeTrue()
        ->and($category->is_active)->toBeTrue();

    // Test toggle
    $newStatus = $service->toggleCategoryStatus($category);
    expect($newStatus)->toBeFalse()
        ->and($category->fresh()->is_active)->toBeFalse();
});

test('ClaimCategoryManagementService enforces role-based category scoping', function () {
    $service = new ClaimCategoryManagementService();

    $driverCategory = ClaimCategory::where('code', 'CAT-DRV-GAS')->first();
    $staffCategory = ClaimCategory::where('code', 'CAT-COMM')->first();
    $allCategory = ClaimCategory::where('code', 'CAT-MED')->first();

    // Driver eligibility
    expect($service->isCategoryApplicableToEmployee($driverCategory, $this->driver))->toBeTrue()
        ->and($service->isCategoryApplicableToEmployee($staffCategory, $this->driver))->toBeFalse()
        ->and($service->isCategoryApplicableToEmployee($allCategory, $this->driver))->toBeTrue();

    // Staff eligibility
    expect($service->isCategoryApplicableToEmployee($driverCategory, $this->staff))->toBeFalse()
        ->and($service->isCategoryApplicableToEmployee($staffCategory, $this->staff))->toBeTrue()
        ->and($service->isCategoryApplicableToEmployee($allCategory, $this->staff))->toBeTrue();
});

test('ClaimTaxabilityService classifies business expenses as 100% non-taxable', function () {
    $service = new ClaimTaxabilityService();
    $gasCategory = ClaimCategory::where('code', 'CAT-DRV-GAS')->first();

    $result = $service->classifyClaim($gasCategory, 4500.00, $this->driver);

    expect($result['tax_classification'])->toBe('non_taxable')
        ->and($result['non_taxable_amount'])->toBe(4500.00)
        ->and($result['taxable_amount'])->toBe(0.00)
        ->and($result['is_capped'])->toBeFalse();
});

test('ClaimTaxabilityService classifies incentives as 100% taxable compensation', function () {
    $service = new ClaimTaxabilityService();
    $incCategory = ClaimCategory::where('code', 'CAT-DRV-INC')->first();

    $result = $service->classifyClaim($incCategory, 2500.00, $this->driver);

    expect($result['tax_classification'])->toBe('taxable')
        ->and($result['non_taxable_amount'])->toBe(0.00)
        ->and($result['taxable_amount'])->toBe(2500.00)
        ->and($result['is_capped'])->toBeFalse();
});

test('ClaimTaxabilityService enforces statutory 10k medical de minimis annual cap and splits excess', function () {
    $service = new ClaimTaxabilityService();
    $medCategory = ClaimCategory::where('code', 'CAT-MED')->first();

    // Claim 1: PHP 6,000 (Within 10,000 cap -> 100% Non-Taxable)
    $claim1Result = $service->classifyClaim($medCategory, 6000.00, $this->staff);

    expect($claim1Result['tax_classification'])->toBe('de_minimis')
        ->and($claim1Result['non_taxable_amount'])->toBe(6000.00)
        ->and($claim1Result['taxable_amount'])->toBe(0.00)
        ->and($claim1Result['annual_cap'])->toBe(10000.00)
        ->and($claim1Result['annual_remaining'])->toBe(10000.00)
        ->and($claim1Result['exceeds_cap'])->toBeFalse();

    // Record Claim 1 as Approved in DB
    Claim::create([
        'employee_id' => $this->staff->id,
        'category_id' => $medCategory->id,
        'receipt_number' => 'MED-REC-001',
        'type' => 'reimbursement',
        'amount' => 6000.00,
        'non_taxable_amount' => 6000.00,
        'taxable_amount' => 0.00,
        'tax_classification' => 'de_minimis',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    // Claim 2: PHP 7,000 (Exceeds remaining 4,000 cap -> 4,000 Non-Taxable + 3,000 Taxable)
    $claim2Result = $service->classifyClaim($medCategory, 7000.00, $this->staff);

    expect($claim2Result['tax_classification'])->toBe('de_minimis')
        ->and($claim2Result['annual_utilized_prior'])->toBe(6000.00)
        ->and($claim2Result['annual_remaining'])->toBe(4000.00)
        ->and($claim2Result['non_taxable_amount'])->toBe(4000.00)
        ->and($claim2Result['taxable_amount'])->toBe(3000.00)
        ->and($claim2Result['exceeds_cap'])->toBeTrue();
});

test('POST /claims/categories creates new category and returns validation feedback', function () {
    $response = $this->post('/claims/categories', [
        'name' => 'Fleet Parking Allowance',
        'code' => 'CAT-PRK-01',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'max_amount' => 2000.00,
        'applicable_to' => 'driver',
        'spending_limit_period' => 'per_month',
        'description' => 'Overnight depot and terminal parking receipts.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('claim_categories', [
        'code' => 'CAT-PRK-01',
        'tax_classification' => 'non_taxable',
        'applicable_to' => 'driver',
    ]);
});

test('Decommissioned tax classification simulator API returns 404', function () {
    $medCategory = ClaimCategory::where('code', 'CAT-MED')->first();

    $response = $this->postJson('/claims/api/tax-classification-simulator', [
        'category_id' => $medCategory->id,
        'amount' => 12500.00,
        'employee_id' => $this->staff->id,
    ]);

    expect($response->status())->toBe(404);
});
