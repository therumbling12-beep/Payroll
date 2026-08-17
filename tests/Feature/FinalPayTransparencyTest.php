<?php

use App\Enums\OffCycleRunType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\OffCyclePayroll;
use App\Models\OffCyclePayrollItem;
use App\Services\Payroll\FinalPaySettlementService;
use Database\Seeders\GovernmentContributionSeeder;
use Database\Seeders\HolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);
    $this->seed(HolidaySeeder::class);

    $this->dept = Department::create(['name' => 'Field Operations']);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-FIN-01',
        'first_name' => 'Roberto',
        'last_name' => 'Alcantara',
        'email' => 'roberto.alcantara@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Supervisor',
        'employment_status' => 'resigned',
        'monthly_rate' => 52000.00,
        'daily_rate' => 2000.00,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '1122334455',
        'hire_date' => '2025-01-01',
    ]);
});

test('final pay settlement service generates comprehensive mathematical breakdown and dole compliance', function () {
    $loan = EmployeeLoan::create([
        'employee_id' => $this->employee->id,
        'loan_type' => 'sss_salary_loan',
        'reference_no' => 'SSS-SETTLE-01',
        'principal_amount' => 15000.00,
        'total_amount_due' => 15000.00,
        'term_months' => 12,
        'semi_monthly_amortization' => 625.00,
        'total_paid' => 5000.00,
        'remaining_balance' => 10000.00,
        'start_date' => '2026-01-01',
        'status' => 'active',
    ]);

    $batch = OffCyclePayroll::create([
        'run_number' => 'OFF-2026-08-01',
        'run_type' => OffCycleRunType::FINAL_PAY,
        'title' => 'Final Pay Settlement - Roberto Alcantara',
        'payout_date' => '2026-08-15',
        'total_gross' => 38000.00,
        'total_deductions' => 10000.00,
        'total_net_pay' => 28000.00,
        'status' => 'draft',
    ]);

    $item = OffCyclePayrollItem::create([
        'off_cycle_payroll_id' => $batch->id,
        'employee_id' => $this->employee->id,
        'basic_pay_earned' => 10000.00, // 5 days x 2000
        'pro_rated_13th_month' => 20000.00,
        'leave_conversion_pay' => 8000.00, // 4 days x 2000
        'bonuses_differentials' => 0.00,
        'reimbursements' => 0.00,
        'gross_amount' => 38000.00,
        'withholding_tax' => 0.00,
        'loan_deduction' => 10000.00,
        'other_deductions' => 0.00,
        'total_deductions' => 10000.00,
        'net_settlement_pay' => 28000.00,
        'computation_breakdown' => [
            'daily_rate' => 2000.00,
            'unpaid_days' => 5.0,
            'unused_leaves' => 4.0,
            'separation_date' => '2026-08-01',
            'active_loan_balance' => 10000.00,
        ],
        'status' => 'calculated',
    ]);

    $service = app(FinalPaySettlementService::class);
    $breakdown = $service->generateDetailedBreakdown($item);

    // 1. Basic Wages Math
    expect($breakdown['basic_wages_math']['basic_pay_earned'])->toBe(10000.00)
        ->and($breakdown['basic_wages_math']['formula'])->toContain('2,000.00 Daily Rate x 5 Unpaid Days');

    // 2. SIL Leave Conversion Math
    expect($breakdown['leave_monetization_math']['leave_conversion_pay'])->toBe(8000.00)
        ->and($breakdown['leave_monetization_math']['formula'])->toContain('2,000.00 Daily Rate x 4 Unused SIL Days');

    // 3. Active Loan Offsets Math
    expect($breakdown['loan_offsets_math']['items'])->toHaveCount(1)
        ->and($breakdown['loan_offsets_math']['items'][0]['balance_before'])->toBe(10000.00)
        ->and($breakdown['loan_offsets_math']['items'][0]['offset_deduction'])->toBe(10000.00)
        ->and($breakdown['loan_offsets_math']['items'][0]['balance_after'])->toBe(0.00);

    // 4. Net Settlement Pay
    expect($breakdown['net_settlement_math']['net_payout'])->toBe(28000.00);

    // 5. DOLE LA 06-20 Compliance
    expect($breakdown['dole_la_06_20_compliance']['statutory_timeline']['passed'])->toBeTrue();
});

test('off cycle item transparency endpoint returns 200 json response with settlement breakdown', function () {
    $batch = OffCyclePayroll::create([
        'run_number' => 'OFF-2026-08-02',
        'run_type' => OffCycleRunType::FINAL_PAY,
        'title' => 'Final Pay Settlement Test',
        'payout_date' => '2026-08-15',
        'total_gross' => 20000.00,
        'total_deductions' => 0.00,
        'total_net_pay' => 20000.00,
        'status' => 'draft',
    ]);

    $item = OffCyclePayrollItem::create([
        'off_cycle_payroll_id' => $batch->id,
        'employee_id' => $this->employee->id,
        'basic_pay_earned' => 20000.00,
        'gross_amount' => 20000.00,
        'total_deductions' => 0.00,
        'net_settlement_pay' => 20000.00,
        'status' => 'calculated',
    ]);

    $response = $this->getJson(route('payroll.off-cycle.item-transparency', $item->id));

    $response->assertOk();
    $response->assertJsonStructure([
        'item_id',
        'batch_id',
        'batch_title',
        'employee' => ['id', 'code', 'name', 'position', 'department', 'hire_date', 'separation_date', 'monthly_rate', 'daily_rate', 'hourly_rate'],
        'basic_wages_math' => ['unpaid_days', 'daily_rate', 'basic_pay_earned', 'formula'],
        'leave_monetization_math' => ['unused_leaves', 'daily_rate', 'leave_conversion_pay', 'formula'],
        'pro_rated_13th_math' => ['separation_date', 'months_worked', 'monthly_rate', 'pro_rated_13th_month', 'non_taxable_exempt', 'taxable_excess', 'formula'],
        'gross_settlement_math' => ['basic_pay_earned', 'pro_rated_13th_month', 'leave_conversion_pay', 'bonuses_differentials', 'reimbursements', 'gross_amount', 'formula'],
        'loan_offsets_math' => ['active_loans_count', 'total_offset_deducted', 'items'],
        'other_deductions_math' => ['clearance_deductions', 'withholding_tax', 'loan_deduction', 'total_deductions', 'formula'],
        'net_settlement_math' => ['gross_amount', 'total_deductions', 'reimbursements', 'net_settlement_pay', 'formula'],
        'dole_la_06_20_compliance',
    ]);
});
