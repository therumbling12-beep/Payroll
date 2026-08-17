<?php

use App\Enums\OffCycleRunType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\OffCyclePayroll;
use App\Models\OffCyclePayrollItem;
use App\Models\SalaryComputation;
use App\Services\Payroll\FinalPaySettlementService;
use App\Services\Payroll\OffCyclePayrollService;
use App\Services\Payroll\ThirteenthMonthService;
use Carbon\Carbon;
use Database\Seeders\GovernmentContributionSeeder;
use Database\Seeders\HolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);
    $this->seed(HolidaySeeder::class);

    $this->dept = Department::create(['name' => 'Operations & Logistics']);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-SEP-001',
        'first_name' => 'Roberto',
        'last_name' => 'Santos',
        'email' => 'roberto.santos@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Logistics Specialist',
        'employment_status' => 'regular',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0055443322',
        'hire_date' => '2025-01-01',
    ]);
});

test('thirteenth month service computes annual dynamic base pay and separation pro ration', function () {
    $year = 2026;

    // Seed 4 cutoffs with 15,000 PHP base pay each = 60,000 PHP total base pay
    for ($i = 1; $i <= 4; $i++) {
        SalaryComputation::create([
            'employee_id' => $this->employee->id,
            'cutoff_period' => "2026-0{$i}-01_15",
            'base_pay' => 15000.00,
            'gross_pay' => 15000.00,
            'total_deductions' => 1500.00,
            'net_pay' => 13500.00,
            'status' => 'released_financial',
        ]);
    }

    $service = app(ThirteenthMonthService::class);

    // 1. Annual calculation: 60,000 / 12 = 5,000.00
    $annual = $service->computeAnnual($year, $this->employee->id);
    expect($annual['employees'][0]['amount'])->toBe(5000.00);

    // 2. Separation pro-ration on April 30, 2026: 60,000 / 12 = 5,000.00
    $separationDate = Carbon::create(2026, 4, 30);
    $proRated = $service->computeProRatedForSeparation($this->employee, $separationDate);
    expect($proRated)->toBe(5000.00);
});

test('final pay settlement service computes unpaid wages pro rated 13th month leaves and loan offsets', function () {
    $separationDate = Carbon::create(2026, 6, 30);

    // Seed active company emergency cash advance
    $loan = EmployeeLoan::create([
        'employee_id' => $this->employee->id,
        'loan_type' => 'company_emergency_loan',
        'reference_no' => 'ADV-FINAL-01',
        'principal_amount' => 10000.00,
        'total_amount_due' => 10000.00,
        'term_months' => 6,
        'semi_monthly_amortization' => 1666.67,
        'total_paid' => 6000.00,
        'remaining_balance' => 4000.00,
        'start_date' => '2026-01-01',
        'status' => 'active',
    ]);

    $service = app(FinalPaySettlementService::class);
    $settlement = $service->computeFinalPay(
        $this->employee,
        $separationDate,
        5.0, // 5 unpaid working days = 5 * 1153.85 = 5769.25
        10.0, // 10 unused leaves = 10 * 1153.85 = 11538.50
        500.00, // other deductions = 500.00
        200.00 // reimbursements = 200.00
    );

    expect($settlement['basic_pay_earned'])->toBe(5769.25)
        ->and($settlement['leave_conversion_pay'])->toBe(11538.50)
        ->and($settlement['pro_rated_13th_month'])->toBe(15000.00)
        ->and($settlement['gross_amount'])->toBe(32307.75)
        ->and($settlement['loan_deduction'])->toBe(4000.00)
        ->and($settlement['net_settlement_pay'])->toBe(28007.75);
});

test('off cycle payroll service creates releases final pay run and clears loan balances', function () {
    $separationDate = Carbon::create(2026, 6, 30);

    $loan = EmployeeLoan::create([
        'employee_id' => $this->employee->id,
        'loan_type' => 'company_emergency_loan',
        'reference_no' => 'ADV-FINAL-02',
        'principal_amount' => 5000.00,
        'total_amount_due' => 5000.00,
        'term_months' => 6,
        'semi_monthly_amortization' => 833.33,
        'total_paid' => 2000.00,
        'remaining_balance' => 3000.00,
        'start_date' => '2026-01-01',
        'status' => 'active',
    ]);

    $service = app(OffCyclePayrollService::class);

    // 1. Create Final Pay Run
    $run = $service->createFinalPayRun([
        'employee_id' => $this->employee->id,
        'separation_date' => '2026-06-30',
        'payout_date' => '2026-07-05',
        'unpaid_days' => 5.0,
        'unused_leaves' => 5.0,
        'other_deductions' => 0.0,
        'reimbursements' => 0.0,
    ]);

    expect($run)->not->toBeNull()
        ->and($run->run_type)->toBe(OffCycleRunType::FINAL_PAY)
        ->and($run->status)->toBe('draft')
        ->and($run->items)->toHaveCount(1);

    // 2. Approve Run
    $service->approveRun($run);
    $run->refresh();
    expect($run->status)->toBe('approved');

    // 3. Release Run
    $service->releaseRun($run);
    $run->refresh();
    expect($run->status)->toBe('released');

    // Loan must be fully paid and employee marked resigned
    $loan->refresh();
    expect((float) $loan->remaining_balance)->toBe(0.00)
        ->and($loan->status)->toBe('fully_paid');

    $this->employee->refresh();
    expect($this->employee->employment_status)->toBe('resigned');
});

test('off cycle web routes allow index viewing creation export and certificate generation', function () {
    // 1. Index
    $resIndex = $this->get(route('payroll.off-cycle'));
    $resIndex->assertOk();
    $resIndex->assertViewIs('payroll-benefits.payroll.off-cycle');

    // 2. Store Final Pay
    $resStore = $this->post(route('payroll.off-cycle.store'), [
        'run_type' => 'final_pay',
        'employee_id' => $this->employee->id,
        'separation_date' => '2026-08-01',
        'payout_date' => '2026-08-05',
        'unpaid_days' => 2,
        'unused_leaves' => 3,
        'other_deductions' => 0,
        'reimbursements' => 0,
    ]);
    $resStore->assertRedirect();

    $run = OffCyclePayroll::latest()->first();
    expect($run)->not->toBeNull();

    // 3. Show Details
    $resShow = $this->get(route('payroll.off-cycle.show', $run->id));
    $resShow->assertOk();
    $resShow->assertViewIs('payroll-benefits.payroll.off-cycle-show');

    // 4. Export CSV
    $resExport = $this->get(route('payroll.off-cycle.export', $run->id));
    $resExport->assertOk();
    $resExport->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // 5. Settlement Certificate
    $item = $run->items->first();
    $resCert = $this->get(route('payroll.off-cycle.certificate', $item->id));
    $resCert->assertOk();
    $resCert->assertViewIs('payroll-benefits.payroll.settlement-certificate');
});
