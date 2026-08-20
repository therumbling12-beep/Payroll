<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Claims\MaternityBenefitService;
use App\Services\Claims\MedicalAssistanceService;
use Database\Seeders\ClaimCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ClaimCategorySeeder::class);
    Storage::fake('public');

    $this->hrDept = Department::create(['name' => 'Human Resources']);

    $this->femaleEmp = Employee::create([
        'employee_code' => 'EMP-MAT-01',
        'first_name' => 'Maria Carmela',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripwise.com',
        'department_id' => $this->hrDept->id,
        'position' => 'Senior HR Specialist',
        'monthly_rate' => 39000.00,
        'daily_rate' => 1500.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(3),
    ]);
});

test('MaternityBenefitService computes 105-day, 120-day, and 60-day RA 11210 benefits', function () {
    $service = new MaternityBenefitService();

    // 1. Standard Live Childbirth: 105 Days
    // SSS MSC = 30,000.00 -> Daily SSS Rate = (6 * 30000) / 180 = 1000.00
    // SSS Share = 1000.00 * 105 = 105,000.00
    // Full Pay = 1500.00 * 105 = 157,500.00
    // Company Differential = 157,500.00 - 105,000.00 = 52,500.00
    $normal = $service->computeMaternityBenefit($this->femaleEmp, 'normal_caesarean');
    expect($normal['leave_days'])->toBe(105)
        ->and($normal['sss_daily_rate'])->toBe(1000.00)
        ->and($normal['sss_maternity_share'])->toBe(105000.00)
        ->and($normal['full_basic_pay'])->toBe(157500.00)
        ->and($normal['company_salary_differential'])->toBe(52500.00)
        ->and($normal['total_advance_amount'])->toBe(157500.00);

    // 2. Solo Parent: 120 Days
    $solo = $service->computeMaternityBenefit($this->femaleEmp, 'solo_parent');
    expect($solo['leave_days'])->toBe(120)
        ->and($solo['sss_maternity_share'])->toBe(120000.00)
        ->and($solo['full_basic_pay'])->toBe(180000.00)
        ->and($solo['company_salary_differential'])->toBe(60000.00)
        ->and($solo['total_advance_amount'])->toBe(180000.00);

    // 3. Miscarriage: 60 Days
    $misc = $service->computeMaternityBenefit($this->femaleEmp, 'miscarriage');
    expect($misc['leave_days'])->toBe(60)
        ->and($misc['sss_maternity_share'])->toBe(60000.00)
        ->and($misc['full_basic_pay'])->toBe(90000.00)
        ->and($misc['company_salary_differential'])->toBe(30000.00)
        ->and($misc['total_advance_amount'])->toBe(90000.00);
});

test('MaternityBenefitService files claim with advance workflow and updates SSS reimbursement status', function () {
    $service = new MaternityBenefitService();
    $cert = UploadedFile::fake()->create('medical_cert.pdf', 300, 'application/pdf');

    $claim = $service->fileMaternityClaim([
        'employee_id' => $this->femaleEmp->id,
        'maternity_type' => 'normal_caesarean',
        'doctor_license_number' => 'PRC-0088123',
        'receipt_number' => 'MAT-2026-001',
        'expense_date' => '2026-07-01',
    ], $cert);

    expect($claim)->toBeInstanceOf(Claim::class)
        ->and($claim->type)->toBe('maternity')
        ->and($claim->tax_classification)->toBe('non_taxable')
        ->and((float) $claim->amount)->toBe(157500.00)
        ->and((float) $claim->sss_maternity_share)->toBe(105000.00)
        ->and((float) $claim->company_maternity_topup)->toBe(52500.00)
        ->and($claim->maternity_leave_days)->toBe(105)
        ->and($claim->sss_reimbursement_status)->toBe('advanced_to_employee')
        ->and($claim->attachment_path)->not->toBeNull();

    // Update SSS Status to Submitted
    $service->updateSssReimbursementStatus($claim, 'submitted_to_sss', 'SSS-REF-9090');
    expect($claim->fresh()->sss_reimbursement_status)->toBe('submitted_to_sss')
        ->and($claim->fresh()->sss_reference_number)->toBe('SSS-REF-9090');

    // Update SSS Status to Reimbursed
    $service->updateSssReimbursementStatus($claim, 'reimbursed_by_sss', 'SSS-REF-9090', '2026-08-10');
    expect($claim->fresh()->sss_reimbursement_status)->toBe('reimbursed_by_sss')
        ->and($claim->fresh()->sss_reimbursement_date->toDateString())->toBe('2026-08-10');
});

test('MedicalAssistanceService files claim and splits amounts based on PHP 10,000 de minimis cap', function () {
    $service = app(MedicalAssistanceService::class);

    // First claim: PHP 7,000 -> 100% Non-Taxable
    $claim1 = $service->fileMedicalClaim([
        'employee_id' => $this->femaleEmp->id,
        'amount' => 7000.00,
        'medical_condition' => 'Dental Extraction & Medicines',
        'merchant_name' => 'Dental Care Clinic',
        'receipt_number' => 'MED-DENT-001',
        'expense_date' => '2026-07-05',
    ]);

    expect((float) $claim1->non_taxable_amount)->toBe(7000.00)
        ->and((float) $claim1->taxable_amount)->toBe(0.00)
        ->and($claim1->expense_subtype)->toBe('medical');

    // Approve claim 1 to register YTD utilization
    $claim1->update(['approval_status' => 'approved']);

    // Second claim: PHP 5,000 -> Remaining cap is PHP 3,000 -> Non-Taxable: 3,000, Taxable: 2,000
    $claim2 = $service->fileMedicalClaim([
        'employee_id' => $this->femaleEmp->id,
        'amount' => 5000.00,
        'medical_condition' => 'Prescription Eye Treatment',
        'merchant_name' => 'Vision Clinic',
        'receipt_number' => 'MED-EYE-002',
        'expense_date' => '2026-07-15',
    ]);

    expect((float) $claim2->non_taxable_amount)->toBe(3000.00)
        ->and((float) $claim2->taxable_amount)->toBe(2000.00);
});

test('POST ess.claims.submit files maternity advance via self-service endpoint', function () {
    $response = $this->post(route('ess.claims.submit'), [
        'employee_id' => $this->femaleEmp->id,
        'type' => 'maternity',
        'maternity_type' => 'solo_parent',
        'amount' => 180000.00,
        'receipt_number' => 'MAT-WEB-001',
        'expense_date' => '2026-07-02',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('claims', [
        'employee_id' => $this->femaleEmp->id,
        'type' => 'maternity',
        'maternity_type' => 'solo_parent',
        'maternity_leave_days' => 120,
    ]);
});

test('MaternityBenefitService computeMaternityBenefit returns live calculation breakdown', function () {
    $service = app(\App\Services\Claims\MaternityBenefitService::class);
    $result = $service->computeMaternityBenefit($this->femaleEmp, 'normal_caesarean');

    expect($result['leave_days'])->toBe(105)
        ->and($result['sss_maternity_share'])->toBe(105000.00)
        ->and($result['company_salary_differential'])->toBe(52500.00)
        ->and($result['total_advance_amount'])->toBe(157500.00)
        ->and($result)->toHaveKeys([
            'employee_id',
            'monthly_rate',
            'daily_rate',
            'maternity_type',
            'leave_days',
            'sss_msc',
            'sss_daily_rate',
            'sss_maternity_share',
            'full_basic_pay',
            'company_salary_differential',
            'total_advance_amount',
            'formula_explanation',
        ]);
});
