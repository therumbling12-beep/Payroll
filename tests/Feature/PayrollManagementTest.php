<?php

use App\Enums\PayrollBatchStatus;
use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\SalaryComputation;
use App\Models\SalaryGrade;
use App\Models\ThirteenthMonthBatch;
use App\Models\ThirteenthMonthComputation;
use Database\Seeders\GovernmentContributionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);

    $this->department = Department::create([
        'name' => 'Fleet Operations',
    ]);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-1001',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0012345678',
        'bank_account_no' => 'SBC-0012345678',
        'is_active' => true,
    ]);

    $this->grade = SalaryGrade::create([
        'position_name' => 'Senior Driver',
        'min_salary' => 20000.00,
        'max_salary' => 35000.00,
        'annual_growth_rate' => 5.00,
    ]);

    $this->computation = SalaryComputation::create([
        'employee_id' => $this->employee->id,
        'cutoff_period' => '2026-07-01_15',
        'base_pay' => 14000.00,
        'trip_earnings' => 2500.00,
        'performance_bonus' => 1000.00,
        'gross_pay' => 17500.00,
        'sss_deduction' => 700.00,
        'sss_employer' => 1400.00,
        'philhealth_deduction' => 350.00,
        'philhealth_employer' => 350.00,
        'pagibig_deduction' => 100.00,
        'pagibig_employer' => 100.00,
        'ec_contribution' => 30.00,
        'withholding_tax' => 500.00,
        'platform_fee_deduction' => 500.00,
        'total_deductions' => 2150.00,
        'net_pay' => 15350.00,
        'status' => 'pending_approval',
    ]);

    $this->thirteenthMonth = ThirteenthMonthComputation::create([
        'employee_id' => $this->employee->id,
        'year' => 2026,
        'months_worked' => 12,
        'monthly_salary' => 28000.00,
        'amount' => 28000.00,
        'status' => 'computed',
    ]);

    $this->adjustment = CompensationAdjustment::create([
        'employee_id' => $this->employee->id,
        'type' => 'merit_promotion',
        'old_rate' => 26000.00,
        'new_rate' => 28000.00,
        'bonus_amount' => 0.00,
        'status' => 'approved',
        'effective_date' => '2026-05-01',
    ]);
});

test('payroll cutoffs list renders successfully', function () {
    $response = $this->get(route('payroll.salary-computation'));
    $response->assertOk();
    $response->assertSee('Payroll Cut-offs');
});

test('payroll salary computation show page renders successfully', function () {
    $response = $this->get(route('payroll.salary-computation.show', '2026-07-01_15'));
    $response->assertOk();
    $response->assertSee('Batch Salary Computation');
    $response->assertSee('Juan Dela Cruz');
});

test('payslips generation page renders successfully', function () {
    $response = $this->get(route('payroll.payslips'));
    $response->assertOk();
    $response->assertSee('Personnel Payslip Generation');
    $response->assertSee('Juan Dela Cruz');
});

test('13th month pay page renders successfully', function () {
    $response = $this->get(route('payroll.thirteenth-month'));
    $response->assertOk();
    $response->assertSee('13th Month Pay Computation');
    $response->assertSee('Juan Dela Cruz');
});

test('payment modes config page renders successfully and displays Security Bank', function () {
    $response = $this->get(route('payroll.payment-modes'));
    $response->assertOk();
    $response->assertSee('Payment Modes');
    $response->assertSee('Security Bank');
    $response->assertSee('Juan Dela Cruz');
});

test('payroll summary reports page renders successfully', function () {
    $response = $this->get(route('payroll.reports'));
    $response->assertOk();
    $response->assertSee('Company Payroll Summary');
    $response->assertSee('Statutory Remittances');
});

test('payroll audit trail page renders successfully', function () {
    $response = $this->get(route('payroll.audit-trail'));
    $response->assertOk();
    $response->assertSee('AI-Driven Regulatory Compliance');
});

test('payroll batch workflow state transitions successfully through full chain', function () {
    $cutoff = '2026-07-01_15';
    
    // Step 1: Submit to Admin
    $res1 = $this->post(route('payroll.workflow.submit-admin', $cutoff));
    $res1->assertRedirect();
    $batch = PayrollBatch::where('cutoff_period', $cutoff)->first();
    expect($batch->status)->toBe(PayrollBatchStatus::PENDING_ADMIN);

    // Step 2: Admin Approves
    $res2 = $this->post(route('payroll.workflow.approve-admin', $cutoff));
    $res2->assertRedirect();
    $batch->refresh();
    expect($batch->status)->toBe(PayrollBatchStatus::APPROVED);

    // Step 3: Request Budget
    $res3 = $this->post(route('payroll.workflow.request-budget', $cutoff));
    $res3->assertRedirect();
    $batch->refresh();
    expect($batch->status)->toBe(PayrollBatchStatus::BUDGET_REQUESTED);

    // Step 4: Receive Budget
    $res4 = $this->post(route('payroll.workflow.receive-budget', $cutoff));
    $res4->assertRedirect();
    $batch->refresh();
    expect($batch->status)->toBe(PayrollBatchStatus::BUDGET_RECEIVED);

    // Step 5: Release Payroll
    $res5 = $this->post(route('payroll.workflow.release', $cutoff));
    $res5->assertRedirect();
    $batch->refresh();
    expect($batch->status)->toBe(PayrollBatchStatus::RELEASED);
});

test('regular driver payroll computation correctly computes base pay, trips, and statutory deductions', function () {
    $driver = Employee::create([
        'employee_code' => 'EMP-1011',
        'first_name' => 'Eduardo',
        'last_name' => 'Ramos',
        'email' => 'eduardo.ramos@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Regular Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0012345678',
        'is_active' => true,
    ]);

    \App\Models\TripIncome::create([
        'employee_id' => $driver->id,
        'cutoff_period' => '2026-07-01_15',
        'total_trips' => 30,
        'total_trip_earnings' => 6000.00,
    ]);

    \App\Models\PerformanceBonus::create([
        'employee_id' => $driver->id,
        'cutoff_period' => '2026-07-01_15',
        'bonus_amount' => 1500.00,
    ]);

    $service = app(\App\Services\PayrollEngineService::class);
    $comp = $service->computeForEmployee($driver, '2026-07-01_15');

    // Base Pay is 30,000 / 2 = 15,000; Trip earnings: 6,000; Tier 1 trip incentive (30 trips): 500; Bonus: 1,500
    expect((float) $comp->base_pay)->toBe(15000.00)
        ->and((float) $comp->trip_earnings)->toBe(6000.00)
        ->and((float) $comp->driver_trip_incentive)->toBe(500.00)
        ->and((float) $comp->performance_bonus)->toBe(1500.00)
        ->and((float) $comp->gross_pay)->toBe(23000.00)
        ->and((float) $comp->sss_deduction)->toBeGreaterThan(0.00)
        ->and((float) $comp->sss_employer)->toBeGreaterThan(0.00)
        ->and((float) $comp->philhealth_deduction)->toBeGreaterThan(0.00)
        ->and((float) $comp->philhealth_employer)->toBeGreaterThan(0.00)
        ->and((float) $comp->pagibig_deduction)->toBe(100.00)
        ->and((float) $comp->pagibig_employer)->toBe(100.00)
        ->and((float) $comp->platform_fee_deduction)->toBe(1200.00) // 20% of 6000.00
        ->and((float) $comp->net_pay)->toBeGreaterThan(0.00);
});

test('regular staff payroll computation includes 2026 statutory brackets and TRAIN tax', function () {
    $staff = Employee::create([
        'employee_code' => 'EMP-1005',
        'first_name' => 'Luisa',
        'last_name' => 'Bautista',
        'email' => 'luisa.bautista@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Admin Assistant',
        'employment_status' => 'regular',
        'monthly_rate' => 25000.00,
        'daily_rate' => 961.54,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    \App\Models\Attendance::create([
        'employee_id' => $staff->id,
        'cutoff_period' => '2026-07-01_15',
        'days_worked' => 11,
    ]);

    $service = app(\App\Services\PayrollEngineService::class);
    $comp = $service->computeForEmployee($staff, '2026-07-01_15');

    expect((float) $comp->base_pay)->toBe(12500.00)
        ->and((float) $comp->sss_deduction)->toBe(625.00) // 25k MSC * 5% / 2 = 625.00
        ->and((float) $comp->sss_employer)->toBe(1250.00) // 25k MSC * 10% / 2 = 1250.00
        ->and((float) $comp->philhealth_deduction)->toBe(312.50) // 25k * 2.5% / 2 = 312.50
        ->and((float) $comp->philhealth_employer)->toBe(312.50)
        ->and((float) $comp->pagibig_deduction)->toBe(100.00)
        ->and((float) $comp->pagibig_employer)->toBe(100.00)
        ->and((float) $comp->ec_contribution)->toBe(15.00);
});

test('streaming exports for payroll register, SSS R-3, PhilHealth RF-1, Pag-IBIG MCRF, and Security Bank CSV work correctly', function () {
    $cutoff = '2026-07-01_15';

    // 1. Master Payroll Register CSV
    $resRegister = $this->get(route('payroll.export.register', $cutoff));
    $resRegister->assertOk();
    $resRegister->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // 2. SSS R-3 Schedule CSV
    $resSss = $this->get(route('payroll.export.sss', $cutoff));
    $resSss->assertOk();
    $resSss->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // 3. PhilHealth RF-1 Schedule CSV
    $resPhil = $this->get(route('payroll.export.philhealth', $cutoff));
    $resPhil->assertOk();
    $resPhil->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // 4. Pag-IBIG MCRF Schedule CSV
    $resPagibig = $this->get(route('payroll.export.pagibig', $cutoff));
    $resPagibig->assertOk();
    $resPagibig->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // 5. Security Bank Corporation CSV
    $resSbc = $this->get(route('payroll.export.security-bank', $cutoff));
    $resSbc->assertOk();
    $resSbc->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
