<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Services\Claims\ClaimGovernanceWorkflowService;
use App\Services\Claims\ClaimsPayrollSyncService;
use App\Services\Claims\DuplicateClaimDetectionService;
use Database\Seeders\ClaimCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ClaimCategorySeeder::class);

    $this->dept = Department::create(['name' => 'Operations']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-GOV-01',
        'first_name' => 'Ricardo',
        'last_name' => 'Dalisay',
        'email' => 'ricardo.dalisay@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Logistics Lead',
        'monthly_rate' => 45000.00,
        'daily_rate' => 1730.77,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    $this->category = ClaimCategory::where('code', 'CAT-DRV-GAS')->first()
        ?: ClaimCategory::create([
            'code' => 'CAT-DRV-GAS',
            'name' => 'Driver Gas Expense',
            'type' => 'reimbursement',
            'tax_classification' => 'non_taxable',
            'color_tag' => 'orange',
            'max_amount' => 10000.00,
            'is_active' => true,
            'applicable_to' => 'all',
        ]);
});

test('ClaimGovernanceWorkflowService executes 4-tier sequential approval workflow', function () {
    $service = new ClaimGovernanceWorkflowService();

    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'category' => $this->category->name,
        'type' => 'expense',
        'expense_subtype' => 'fuel',
        'receipt_number' => 'OR-SEQ-001',
        'amount' => 3000.00,
        'non_taxable_amount' => 3000.00,
        'taxable_amount' => 0.00,
        'tax_classification' => 'non_taxable',
        'expense_date' => '2026-07-01',
        'cutoff_period' => '2026-07-01_15',
        'approval_status' => 'pending',
        'status' => 'pending',
    ]);

    // 1. Supervisor Review
    $service->approveSupervisor($claim, null, 'Supervisor initial check ok.');
    expect($claim->fresh()->approval_status)->toBe('pending_hr')
        ->and($claim->fresh()->supervisor_approved_at)->not->toBeNull();

    // 2. HR Validation
    $service->approveHR($claim, null, 'HR verified receipt.');
    expect($claim->fresh()->approval_status)->toBe('pending_admin')
        ->and($claim->fresh()->hr_approved_at)->not->toBeNull();

    // 3. Admin Review & Authorization
    $service->approveAdmin($claim, null, 'Executive authorized.');
    expect($claim->fresh()->approval_status)->toBe('pending_finance')
        ->and($claim->fresh()->admin_approved_at)->not->toBeNull();

    // 4. Finance Budget Approval
    $service->approveFinance($claim, null, 'Finance budget allocated.');
    expect($claim->fresh()->approval_status)->toBe('approved')
        ->and($claim->fresh()->status)->toBe('approved')
        ->and($claim->fresh()->finance_approved_at)->not->toBeNull();

    // 5. Queue to Payroll
    $service->queueToPayroll($claim);
    expect($claim->fresh()->approval_status)->toBe('payroll_queued')
        ->and($claim->fresh()->payroll_queued_at)->not->toBeNull();

    // 6. Mark Paid
    $service->markPaid($claim);
    expect($claim->fresh()->approval_status)->toBe('paid')
        ->and($claim->fresh()->status)->toBe('paid')
        ->and($claim->fresh()->paid_at)->not->toBeNull();
});

test('ClaimGovernanceWorkflowService applies partial approval reduction proportionally', function () {
    $service = new ClaimGovernanceWorkflowService();

    // Original Claim: 10,000 Total (8,000 Non-taxable, 2,000 Taxable)
    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'category' => $this->category->name,
        'type' => 'expense',
        'receipt_number' => 'OR-PARTIAL-001',
        'amount' => 10000.00,
        'non_taxable_amount' => 8000.00,
        'taxable_amount' => 2000.00,
        'tax_classification' => 'de_minimis',
        'expense_date' => '2026-07-02',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    // Partially approve PHP 5,000.00 (50% reduction)
    $service->approveHR($claim, 5000.00, 'Partial approval granted due to policy cap.');

    $fresh = $claim->fresh();
    expect((float) $fresh->amount)->toBe(5000.00)
        ->and((float) $fresh->non_taxable_amount)->toBe(4000.00)
        ->and((float) $fresh->taxable_amount)->toBe(1000.00)
        ->and($fresh->approval_status)->toBe('pending_admin');
});

test('ClaimGovernanceWorkflowService rejects claim with mandatory reason', function () {
    $service = new ClaimGovernanceWorkflowService();

    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'category' => $this->category->name,
        'type' => 'expense',
        'receipt_number' => 'OR-REJECT-001',
        'amount' => 2500.00,
        'non_taxable_amount' => 2500.00,
        'taxable_amount' => 0.00,
        'expense_date' => '2026-07-03',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    $service->rejectClaim($claim, 'Official receipt is blurred and non-readable.', 'HR Reviewer');

    $fresh = $claim->fresh();
    expect($fresh->status)->toBe('rejected')
        ->and($fresh->approval_status)->toBe('rejected')
        ->and($fresh->rejection_reason)->toBe('Official receipt is blurred and non-readable.')
        ->and($fresh->rejected_by)->toBe('HR Reviewer')
        ->and($fresh->rejected_at)->not->toBeNull();
});

test('DuplicateClaimDetectionService detects exact receipt and amount-date collisions', function () {
    $detector = new DuplicateClaimDetectionService();

    Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'category' => $this->category->name,
        'type' => 'expense',
        'receipt_number' => 'OR-PETRON-9988',
        'amount' => 1800.00,
        'non_taxable_amount' => 1800.00,
        'taxable_amount' => 0.00,
        'expense_date' => '2026-07-05',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    // 1. Exact Receipt Match Test
    $res1 = $detector->checkDuplicate($this->employee->id, 'OR-PETRON-9988', 1800.00, '2026-07-05');
    expect($res1['is_duplicate'])->toBeTrue()
        ->and($res1['risk_level'])->toBe('HIGH_RISK')
        ->and($res1['risk_score'])->toBeGreaterThanOrEqual(90);

    // 2. Different Receipt but Same Employee, Same Amount, Same Date
    $res2 = $detector->checkDuplicate($this->employee->id, 'OR-OTHER-0001', 1800.00, '2026-07-05');
    expect($res2['is_duplicate'])->toBeTrue()
        ->and($res2['risk_level'])->toBe('HIGH_RISK');

    // 3. Unique Claim
    $res3 = $detector->checkDuplicate($this->employee->id, 'OR-UNIQUE-8888', 2500.00, '2026-08-01');
    expect($res3['is_duplicate'])->toBeFalse()
        ->and($res3['risk_level'])->toBe('NONE');
});

test('ClaimsPayrollSyncService updates SalaryComputation with non-taxable reimbursements', function () {
    $syncService = new ClaimsPayrollSyncService();

    // Create an initial SalaryComputation record
    SalaryComputation::create([
        'employee_id' => $this->employee->id,
        'cutoff_period' => '2026-07-01_15',
        'basic_pay' => 22500.00,
        'gross_pay' => 22500.00,
        'total_deductions' => 2000.00,
        'reimbursements' => 0.00,
        'net_pay' => 20500.00,
    ]);

    // Create 2 Approved Claims
    Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'category' => $this->category->name,
        'type' => 'expense',
        'receipt_number' => 'SYNC-OR-01',
        'amount' => 1500.00,
        'non_taxable_amount' => 1500.00,
        'taxable_amount' => 0.00,
        'cutoff_period' => '2026-07-01_15',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'category' => $this->category->name,
        'type' => 'expense',
        'receipt_number' => 'SYNC-OR-02',
        'amount' => 2500.00,
        'non_taxable_amount' => 2500.00,
        'taxable_amount' => 0.00,
        'cutoff_period' => '2026-07-01_15',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    $result = $syncService->syncApprovedClaimsToPayroll('2026-07-01_15');

    expect($result['synced_claims_count'])->toBe(2)
        ->and($result['total_non_taxable_reimbursements'])->toBe(4000.00);

    $computation = SalaryComputation::where('employee_id', $this->employee->id)->where('cutoff_period', '2026-07-01_15')->first();
    expect((float) $computation->reimbursements)->toBe(4000.00)
        ->and((float) $computation->net_pay)->toBe(24500.00); // 22,500 gross - 2,000 ded + 4,000 reimb
});

test('Decommissioned document archive route /claims/archive returns 404', function () {
    $response = $this->get('/claims/archive');
    expect($response->status())->toBe(404);
});
