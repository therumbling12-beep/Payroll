<?php

declare(strict_types=1);

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Services\Compensation\AuditTrailExportService;
use App\Services\Compensation\CompensationApprovalService;
use App\Services\Compensation\CounterOfferService;
use App\Services\Compensation\RetroactivePayCalculationService;
use App\Services\Compensation\SalaryDeterminationService;
use App\Services\FinancialService;
use Database\Seeders\SalaryGradeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SalaryGradeSeeder::class);

    $this->dept = Department::create(['name' => 'Logistics & Fleet Operations']);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-GOV-01',
        'first_name' => 'Danilo',
        'last_name' => 'Navarro',
        'email' => 'danilo.navarro@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Operations Dispatcher',
        'employment_status' => 'regular',
        'monthly_rate' => 25000.00,
        'hire_date' => now()->subYears(2),
    ]);

    $this->adjustment = CompensationAdjustment::create([
        'employee_id' => $this->employee->id,
        'subject_type' => 'employee',
        'type' => 'merit_increase',
        'mode' => 'mode_a',
        'old_rate' => 25000.00,
        'new_rate' => 28000.00,
        'monthly_ctc' => 31780.00,
        'annual_ctc' => 381360.00,
        'thirteenth_month_liability' => 2333.33,
        'employer_statutory_total' => 3780.00,
        'old_position' => 'Senior Operations Dispatcher',
        'new_position' => 'Fleet Supervisor',
        'status' => 'pending',
        'budget_impact_status' => 'PENDING_FINANCE_VALIDATION',
        'admin_approval_status' => 'PENDING_ADMIN_APPROVAL',
        'effective_date' => now()->subDays(10), // Retroactive 10 days
        'reason' => 'Promotion with 15% increment approved by Team 3',
    ]);
});

test('CompensationApprovalService validates Team 5 budget, grants Team 8 admin approval and syncs to payroll', function () {
    $salaryDeterminationService = new SalaryDeterminationService();
    $counterOfferService = new CounterOfferService($salaryDeterminationService);
    $retroactivePayService = new RetroactivePayCalculationService();
    $financialService = new FinancialService();

    $service = new CompensationApprovalService($financialService, $retroactivePayService, $counterOfferService);

    // 1. Submit for Team 5 Finance Validation
    $financeCheck = $service->submitForFinanceValidation($this->adjustment);
    expect($financeCheck['status'])->toBe('BUDGET_APPROVED')
        ->and($financeCheck['approved'])->toBeTrue();

    $this->adjustment->refresh();
    expect($this->adjustment->budget_impact_status)->toBe('BUDGET_APPROVED');

    // 2. Grant Team 8 Administrative Approval
    $adminApproved = $service->grantAdminApproval($this->adjustment, 'Atty. Santos - Corporate Admin', 'Approved compliance check.');
    expect($adminApproved)->toBeTrue();

    $this->adjustment->refresh();
    expect($this->adjustment->admin_approval_status)->toBe('ADMIN_APPROVED')
        ->and($this->adjustment->admin_approved_by)->toBe('Atty. Santos - Corporate Admin');

    // 3. Finalize & Sync to Real-Time Payroll
    $synced = $service->finalizeAndSyncToPayroll($this->adjustment);
    expect($synced)->toBeTrue();

    $this->adjustment->refresh();
    expect($this->adjustment->status)->toBe('approved');

    $this->employee->refresh();
    expect((float) $this->employee->monthly_rate)->toBe(28000.00)
        ->and($this->employee->position)->toBe('Fleet Supervisor');

    // Verify Audit Trail Logged
    $this->assertDatabaseHas('payroll_audit_trails', [
        'action' => 'COMPENSATION_ADJUSTMENT_FINALIZED_PAYROLL_SYNC',
        'model_id' => $this->adjustment->id,
    ]);
});

test('CompensationApprovalService handles employee response workflow', function () {
    $salaryDeterminationService = new SalaryDeterminationService();
    $counterOfferService = new CounterOfferService($salaryDeterminationService);
    $retroactivePayService = new RetroactivePayCalculationService();
    $financialService = new FinancialService();

    $service = new CompensationApprovalService($financialService, $retroactivePayService, $counterOfferService);

    $recorded = $service->recordEmployeeResponse($this->adjustment, 'accepted');
    expect($recorded)->toBeTrue();

    $this->adjustment->refresh();
    expect($this->adjustment->employee_response)->toBe('accepted');
});

test('AuditTrailExportService streams valid RFC 4180 CSV export with 200 OK', function () {
    PayrollAuditTrail::create([
        'action' => 'COMPENSATION_AUDIT_VERIFIED',
        'model_type' => Employee::class,
        'model_id' => $this->employee->id,
        'user_name' => 'Internal Auditor',
        'ip_address' => '127.0.0.1',
        'old_values' => ['monthly_rate' => 25000.00],
        'new_values' => ['monthly_rate' => 28000.00],
    ]);

    $response = $this->get('/compensation/audit-trail/export');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

test('POST /compensation/api/finance-budget-validation and /adjustments/{id}/admin-approve execute successfully', function () {
    // 1. Finance Validation Endpoint
    $financeRes = $this->postJson('/compensation/api/finance-budget-validation', [
        'adjustment_id' => $this->adjustment->id,
    ]);
    $financeRes->assertOk()
        ->assertJsonStructure([
            'status',
            'approved',
            'monthly_ctc',
            'reason',
        ]);

    // 2. Admin Approval Endpoint
    $adminRes = $this->post("/compensation/adjustments/{$this->adjustment->id}/admin-approve", [
        'admin_name' => 'Legal Compliance Admin',
        'admin_notes' => 'Passed corporate vetting.',
    ]);
    $adminRes->assertRedirect();

    $this->adjustment->refresh();
    expect($this->adjustment->admin_approval_status)->toBe('ADMIN_APPROVED');
});
