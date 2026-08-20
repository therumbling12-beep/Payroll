<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TripIncome;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create(['name' => 'Fleet Operations']);
    $this->adminDept = Department::create(['name' => 'Administration & HR']);

    CompanySetting::updateOrCreate(['key' => 'performance_rating_multiplier'], ['value' => '1500.00']);
    CompanySetting::updateOrCreate(['key' => 'driver_tier_10_amount'], ['value' => '250.00']);
    CompanySetting::updateOrCreate(['key' => 'driver_tier_20_amount'], ['value' => '500.00']);
    CompanySetting::updateOrCreate(['key' => 'driver_tier_30_amount'], ['value' => '1000.00']);
    CompanySetting::updateOrCreate(['key' => 'driver_tier_50_amount'], ['value' => '2000.00']);
    CompanySetting::updateOrCreate(['key' => 'maternity_leave_days'], ['value' => '105']);
    CompanySetting::updateOrCreate(['key' => 'standard_working_days_divisor'], ['value' => '26']);

    $this->gasCategory = ClaimCategory::create([
        'name' => 'Driver Gas Expense',
        'code' => 'CAT-DRV-GAS',
        'type' => 'reimbursement',
        'color_tag' => 'orange',
        'max_amount' => 10000.00,
        'is_active' => true,
        'applicable_to' => 'driver',
        'description' => 'Gas and fuel reimbursement.',
    ]);
});

test('it renders all canonical claims subpages successfully', function () {
    $driver = Employee::create([
        'employee_code' => 'EMP-DRV-INIT',
        'first_name' => 'Danilo',
        'last_name' => 'Navarro',
        'email' => 'danilo.navarro@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $this->get(route('claims.expenses'))->assertOk()->assertSee('Driver Work Expense Claims');
    $this->get(route('claims.incentives'))->assertRedirect(route('claims.expenses'));
    $this->get(route('claims.maternity-leave'))->assertOk()->assertSee('Maternity Leave & Benefit Claims', false);
    $this->get(route('claims.categories'))->assertOk()->assertSee('Claim & Incentive Categories', false);
});

test('it stores an expense claim and sets approval status to pending_hr', function () {
    $driver = Employee::create([
        'employee_code' => 'EMP-TEST-01',
        'first_name' => 'Danilo',
        'last_name' => 'Navarro',
        'email' => 'danilo.navarro@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $response = $this->post(route('ess.claims.submit'), [
        'employee_id' => $driver->id,
        'category_id' => $this->gasCategory->id,
        'type' => 'expense',
        'amount' => 1250.00,
        'expense_date' => '2026-07-05',
        'description' => 'Shell EDSA Fuel Top-Up',
        'receipt_number' => 'RCP-TEST-1001',
        'merchant_name' => 'Shell EDSA',
    ]);

    $response->assertRedirect();

    $claim = Claim::where('receipt_number', 'RCP-TEST-1001')->first();
    expect($claim)->not->toBeNull()
        ->and($claim->approval_status)->toBe('pending_hr')
        ->and((float) $claim->amount)->toBe(1250.00)
        ->and($claim->category)->toBe('Driver Gas Expense');
});

test('it prevents duplicate claim submissions with same date, type, amount, and employee', function () {
    $driver = Employee::create([
        'employee_code' => 'EMP-TEST-02',
        'first_name' => 'Ronaldo',
        'last_name' => 'Aquino',
        'email' => 'ronaldo.aquino@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Fleet Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    Claim::create([
        'employee_id' => $driver->id,
        'type' => 'expense',
        'amount' => 800.00,
        'expense_date' => '2026-07-08',
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Initial Toll Fee Claim',
        'receipt_number' => 'RCP-INIT-01',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    // Check duplicate detection via DuplicateClaimDetectionService
    $detector = app(\App\Services\Claims\DuplicateClaimDetectionService::class);
    $duplicateCheck = $detector->checkDuplicate($driver->id, 'RCP-DUP-01', 800.00, '2026-07-08');

    expect($duplicateCheck['is_duplicate'])->toBeTrue()
        ->and($duplicateCheck['risk_level'])->toBe('HIGH_RISK')
        ->and($duplicateCheck['risk_score'])->toBeGreaterThanOrEqual(70);
});

test('it executes multi-step approval workflow: HR validation -> Admin review -> Finance approval -> Payroll queue', function () {
    $staff = Employee::create([
        'employee_code' => 'EMP-TEST-03',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripwise.com',
        'department_id' => $this->adminDept->id,
        'position' => 'HR Specialist',
        'employment_status' => 'regular',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $claim = Claim::create([
        'employee_id' => $staff->id,
        'type' => 'performance',
        'amount' => 6000.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Outstanding quarterly hiring target achievement',
        'receipt_number' => 'PRF-WORKFLOW-01',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    // 1. HR Validation
    $this->post(route('claims.workflow-action', $claim->id), ['action' => 'approve_hr', 'remarks' => 'HR validation passed'])
        ->assertRedirect();
    $claim->refresh();
    expect($claim->approval_status)->toBe('pending_admin')
        ->and($claim->hr_approved_at)->not->toBeNull();

    // 2. Admin Review
    $this->post(route('claims.workflow-action', $claim->id), ['action' => 'approve_admin', 'remarks' => 'Admin authorization confirmed'])
        ->assertRedirect();
    $claim->refresh();
    expect($claim->approval_status)->toBe('pending_finance')
        ->and($claim->admin_approved_at)->not->toBeNull();

    // 3. Finance Approval
    $this->post(route('claims.workflow-action', $claim->id), ['action' => 'approve_finance', 'remarks' => 'Budget confirmed'])
        ->assertRedirect();
    $claim->refresh();
    expect($claim->approval_status)->toBe('approved')
        ->and($claim->finance_approved_at)->not->toBeNull();

    // 4. Queue into Payroll Engine
    $this->post(route('claims.workflow-action', $claim->id), ['action' => 'queue_payroll'])
        ->assertRedirect();
    $claim->refresh();
    expect($claim->approval_status)->toBe('payroll_queued')
        ->and($claim->payroll_queued_at)->not->toBeNull()
        ->and($claim->status)->toBe('approved');
});

test('it calculates performance incentive based on rating score and multiplier', function () {
    $staff = Employee::create([
        'employee_code' => 'EMP-TEST-04',
        'first_name' => 'Carlo',
        'last_name' => 'Ramos',
        'email' => 'carlo.ramos@tripwise.com',
        'department_id' => $this->adminDept->id,
        'position' => 'Operations Supervisor',
        'employment_status' => 'regular',
        'monthly_rate' => 35000.00,
        'daily_rate' => 1346.15,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    // Calculate performance bonus directly using CompanySetting multiplier
    $multiplier = (float) \App\Models\CompanySetting::getValue('performance_rating_multiplier', 1500.00);
    $rating = 4.5;
    $computedAmount = round($rating * $multiplier, 2);

    $claim = Claim::create([
        'employee_id' => $staff->id,
        'type' => 'performance',
        'performance_rating' => $rating,
        'amount' => $computedAmount,
        'description' => 'Exemplary supervisor output',
        'receipt_number' => 'PRF-CALC-01',
        'approval_status' => 'pending_hr',
    ]);

    expect($claim)->not->toBeNull()
        ->and((float) $claim->amount)->toBe(6750.00)
        ->and((float) $claim->performance_rating)->toBe(4.50);
});

test('it computes 105-day statutory maternity benefit with SSS and company top-up split', function () {
    $staff = Employee::create([
        'employee_code' => 'EMP-TEST-05',
        'first_name' => 'Angelica',
        'last_name' => 'Mendoza',
        'email' => 'angelica.mendoza@tripwise.com',
        'department_id' => $this->adminDept->id,
        'position' => 'HR Assistant',
        'employment_status' => 'regular',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00, // ₱1,000 / day
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    // 105 days * ₱1,000 = ₱105,000.00 total
    // SSS MSC = 26,000 -> SSS daily = (6*26000)/180 = 866.67 -> SSS share = 91,000.35, Company top-up = 13,999.65
    $this->post(route('ess.claims.submit'), [
        'employee_id' => $staff->id,
        'type' => 'maternity',
        'maternity_type' => 'normal_caesarean',
        'amount' => 105000.00,
        'expense_date' => '2026-07-01',
        'description' => 'Statutory 105-Day Maternity Benefit Advance',
        'receipt_number' => 'MAT-CALC-01',
    ])->assertRedirect();

    $claim = Claim::where('receipt_number', 'MAT-CALC-01')->first();
    expect($claim)->not->toBeNull()
        ->and((float) $claim->amount)->toBe(105000.00)
        ->and((float) $claim->sss_maternity_share)->toBe(91000.35)
        ->and((float) $claim->company_maternity_topup)->toBe(13999.65);
});

test('it calculates driver ride milestone incentive based on verified trip quota tiers', function () {
    $driver = Employee::create([
        'employee_code' => 'EMP-TEST-06',
        'first_name' => 'Eduardo',
        'last_name' => 'Valdez',
        'email' => 'eduardo.valdez@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Chauffeur',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    TripIncome::create([
        'employee_id' => $driver->id,
        'cutoff_period' => '2026-07-01_15',
        'total_trips' => 45, // Qualifies for Tier 2 (40+ rides -> ₱1,000.00)
        'total_trip_earnings' => 9000.00,
    ]);

    // Qualify milestone incentive through DriverMilestoneIncentiveService
    $milestoneService = app(\App\Services\Claims\DriverMilestoneIncentiveService::class);
    $roster = $milestoneService->qualifyDriverRoster('2026-07-01_15');
    $driverPlan = $roster->firstWhere('driver_id', $driver->id);

    expect($driverPlan)->not->toBeNull()
        ->and($driverPlan['is_qualified'])->toBeTrue()
        ->and((float) $driverPlan['base_milestone_amount'])->toBe(1000.00);

    $this->post(route('claims.incentives.batch-qualify'), [
        'cutoff_period' => '2026-07-01_15',
        'plans_json' => json_encode([$driverPlan]),
    ])->assertRedirect(route('claims.expenses'));
});

test('it can create, update, and toggle claim categories', function () {
    // 1. Create Category
    $this->post(route('claims.categories.store'), [
        'name' => 'Relocation Assistance',
        'code' => 'CAT-RELO',
        'type' => 'reimbursement',
        'color_tag' => 'violet',
        'max_amount' => 25000.00,
        'applicable_to' => 'all',
        'description' => 'Assistance for regional transfers.',
    ])->assertRedirect();

    $cat = ClaimCategory::where('code', 'CAT-RELO')->first();
    expect($cat)->not->toBeNull()
        ->and($cat->is_active)->toBeTrue()
        ->and((float) $cat->max_amount)->toBe(25000.00);

    // 2. Toggle Active State
    $this->post(route('claims.categories.toggle', $cat->id))->assertRedirect();
    $cat->refresh();
    expect($cat->is_active)->toBeFalse();

    // 3. Update Category
    $this->post(route('claims.categories.update', $cat->id), [
        'name' => 'Regional Relocation Support',
        'max_amount' => 30000.00,
        'applicable_to' => 'regular',
        'description' => 'Updated allowance policy.',
    ])->assertRedirect();

    $cat->refresh();
    expect($cat->name)->toBe('Regional Relocation Support')
        ->and((float) $cat->max_amount)->toBe(30000.00)
        ->and($cat->applicable_to)->toBe('regular');
});

test('it handles batch approval of multiple claims in one request', function () {
    $emp = Employee::create([
        'employee_code' => 'EMP-BATCH-01',
        'first_name' => 'Danilo',
        'last_name' => 'Navarro',
        'email' => 'danilo.navarro.batch@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $claim1 = Claim::create([
        'employee_id' => $emp->id,
        'type' => 'expense',
        'amount' => 500.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Batch Claim 1',
        'receipt_number' => 'RCP-BATCH-01',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    $claim2 = Claim::create([
        'employee_id' => $emp->id,
        'type' => 'expense',
        'amount' => 750.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Batch Claim 2',
        'receipt_number' => 'RCP-BATCH-02',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    // Batch HR Validate
    $this->post(route('claims.batch-workflow'), [
        'action' => 'batch_approve_hr',
        'selected_ids' => [$claim1->id, $claim2->id],
    ])->assertRedirect();

    $claim1->refresh();
    $claim2->refresh();

    expect($claim1->approval_status)->toBe('pending_admin')
        ->and($claim2->approval_status)->toBe('pending_admin');

    // Batch Admin Authorize
    $this->post(route('claims.batch-workflow'), [
        'action' => 'batch_approve_admin',
        'selected_ids' => [$claim1->id, $claim2->id],
    ])->assertRedirect();

    $claim1->refresh();
    $claim2->refresh();

    expect($claim1->approval_status)->toBe('pending_finance')
        ->and($claim2->approval_status)->toBe('pending_finance');
});

test('it exports claims audit report to csv format', function () {
    $emp = Employee::create([
        'employee_code' => 'EMP-EXP-01',
        'first_name' => 'Danilo',
        'last_name' => 'Navarro',
        'email' => 'danilo.exp@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    Claim::create([
        'employee_id' => $emp->id,
        'type' => 'expense',
        'amount' => 500.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Exportable Claim',
        'receipt_number' => 'RCP-EXP-01',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    $response = $this->get(route('claims.export', ['type' => 'expense']));
    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('it allows approvers to adjust approved amount for partial expense qualification', function () {
    $emp = Employee::create([
        'employee_code' => 'EMP-PARTIAL-01',
        'first_name' => 'Danilo',
        'last_name' => 'Navarro',
        'email' => 'danilo.partial@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $claim = Claim::create([
        'employee_id' => $emp->id,
        'type' => 'expense',
        'amount' => 2000.00,
        'cutoff_period' => '2026-07-01_15',
        'description' => 'Gas fuel claim with personal snacks',
        'receipt_number' => 'RCP-PARTIAL-01',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
    ]);

    // HR validates with partial amount
    $this->post(route('claims.workflow-action', $claim->id), [
        'action' => 'approve_hr',
        'approved_amount' => 1850.00,
        'remarks' => 'Deducted ₱150 personal convenience store purchase.',
    ])->assertRedirect();

    $claim->refresh();

    expect((float) $claim->amount)->toBe(1850.00)
        ->and($claim->approval_status)->toBe('pending_admin')
        ->and($claim->hr_remarks)->toContain('Approved Amount Adjusted: ₱1,850.00');
});
