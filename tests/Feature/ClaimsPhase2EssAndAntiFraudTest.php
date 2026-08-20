<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Claims\DuplicateClaimDetectionService;
use App\Services\Claims\MedicalAssistanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create([
        'name' => 'Transport Operations',
        'code' => 'OPS-TRN',
    ]);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-P2-01',
        'first_name' => 'Carlos',
        'last_name' => 'Mendoza',
        'email' => 'carlos.mendoza@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Logistics Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $this->gasCategory = ClaimCategory::create([
        'name' => 'Driver Gas Expense',
        'code' => 'CAT-DRV-GAS',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'color_tag' => 'orange',
        'max_amount' => 10000.00,
        'requires_receipt' => true,
        'applicable_to' => 'driver',
        'is_active' => true,
    ]);

    $this->medCategory = ClaimCategory::create([
        'name' => 'Medical Assistance',
        'code' => 'CAT-MED',
        'type' => 'reimbursement',
        'tax_classification' => 'de_minimis',
        'color_tag' => 'emerald',
        'max_amount' => 15000.00,
        'de_minimis_annual_cap' => 10000.00,
        'requires_receipt' => true,
        'applicable_to' => 'all',
        'is_active' => true,
    ]);
});

test('ESS medical claim submission delegates to MedicalAssistanceService and applies de minimis cap', function () {
    $response = $this->post(route('ess.claims.submit'), [
        'employee_id' => $this->driver->id,
        'type' => 'medical',
        'category_id' => $this->medCategory->id,
        'amount' => 12500.00,
        'receipt_number' => 'MED-RX-2026-001',
        'merchant_name' => 'Mercury Drug EDSA',
        'expense_date' => '2026-07-15',
        'description' => 'Prescription medicines and diagnostic lab work',
    ]);

    $response->assertRedirect();

    $claim = Claim::where('receipt_number', 'MED-RX-2026-001')->first();
    expect($claim)->not->toBeNull()
        ->and((float) $claim->amount)->toBe(12500.00)
        ->and((float) $claim->non_taxable_amount)->toBe(10000.00) // BIR TRAIN Law ₱10k cap
        ->and((float) $claim->taxable_amount)->toBe(2500.00)     // Excess is taxable compensation
        ->and($claim->approval_status)->toBe('pending_hr')
        ->and($claim->is_duplicate_flagged)->toBeFalse()
        ->and($claim->duplicate_risk_score)->toBe(0);
});

test('Anti-fraud engine flags exact Official Receipt collision across different claims', function () {
    // 1. Initial legitimate claim
    Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->gasCategory->id,
        'type' => 'expense',
        'amount' => 1500.00,
        'receipt_number' => 'OR-COLLISION-999',
        'expense_date' => '2026-07-10',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    // 2. Second claim with same OR receipt number submitted by another driver
    $otherDriver = Employee::create([
        'employee_code' => 'DRV-P2-02',
        'first_name' => 'Roberto',
        'last_name' => 'Santos',
        'email' => 'roberto.santos@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Junior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 20000.00,
        'daily_rate' => 769.23,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $response = $this->post(route('ess.claims.submit'), [
        'employee_id' => $otherDriver->id,
        'type' => 'expense',
        'category_id' => $this->gasCategory->id,
        'amount' => 1500.00,
        'receipt_number' => 'OR-COLLISION-999',
        'expense_date' => '2026-07-11',
        'description' => 'Duplicate fuel claim attempt with cloned OR number',
    ]);

    $response->assertRedirect();

    $duplicateClaim = Claim::where('employee_id', $otherDriver->id)
        ->where('receipt_number', 'OR-COLLISION-999')
        ->first();

    expect($duplicateClaim)->not->toBeNull()
        ->and($duplicateClaim->is_duplicate_flagged)->toBeTrue()
        ->and($duplicateClaim->duplicate_risk_score)->toBeGreaterThanOrEqual(95)
        ->and($duplicateClaim->duplicate_match_details)->not->toBeEmpty();
});

test('Anti-fraud engine flags identical employee amount and expense date collision', function () {
    // 1. Initial legitimate claim
    Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->gasCategory->id,
        'type' => 'expense',
        'amount' => 2450.00,
        'receipt_number' => 'OR-LEGIT-001',
        'expense_date' => '2026-07-20',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    // 2. Second claim with different OR number but same employee + exact amount + same date
    $response = $this->post(route('ess.claims.submit'), [
        'employee_id' => $this->driver->id,
        'type' => 'expense',
        'category_id' => $this->gasCategory->id,
        'amount' => 2450.00,
        'receipt_number' => 'OR-DIFFERENT-OR-SAME-DAY',
        'expense_date' => '2026-07-20',
        'description' => 'Same date identical amount double filing',
    ]);

    $response->assertRedirect();

    $flaggedClaim = Claim::where('receipt_number', 'OR-DIFFERENT-OR-SAME-DAY')->first();
    expect($flaggedClaim)->not->toBeNull()
        ->and($flaggedClaim->is_duplicate_flagged)->toBeTrue()
        ->and($flaggedClaim->duplicate_risk_score)->toBeGreaterThanOrEqual(90);
});

test('Anti-fraud engine does not flag legitimate distinct claims with unique receipts and dates', function () {
    $response = $this->post(route('ess.claims.submit'), [
        'employee_id' => $this->driver->id,
        'type' => 'expense',
        'category_id' => $this->gasCategory->id,
        'amount' => 1350.00,
        'receipt_number' => 'OR-CLEAN-UNIQUE-777',
        'expense_date' => '2026-07-25',
        'description' => 'Clean standalone fuel claim',
    ]);

    $response->assertRedirect();

    $cleanClaim = Claim::where('receipt_number', 'OR-CLEAN-UNIQUE-777')->first();
    expect($cleanClaim)->not->toBeNull()
        ->and($cleanClaim->is_duplicate_flagged)->toBeFalse()
        ->and($cleanClaim->duplicate_risk_score)->toBe(0);
});
