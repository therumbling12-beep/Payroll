<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanAmortizationLog;
use App\Models\SalaryComputation;
use App\Models\TripIncome;
use App\Services\Payroll\DriverTripIncentiveService;
use App\Services\Payroll\LoanAmortizationService;
use App\Services\PayrollEngineService;
use Database\Seeders\GovernmentContributionSeeder;
use Database\Seeders\HolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);
    $this->seed(HolidaySeeder::class);

    $this->fleetDept = Department::create(['name' => 'Fleet Operations']);
    $this->adminDept = Department::create(['name' => 'Administration & HR']);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-1001',
        'first_name' => 'Danilo',
        'last_name' => 'Reyes',
        'email' => 'danilo.reyes@tripwise.com',
        'department_id' => $this->fleetDept->id,
        'position' => 'Senior Fleet Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 0.00,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0098765432',
        'is_active' => true,
    ]);

    $this->staff = Employee::create([
        'employee_code' => 'EMP-2001',
        'first_name' => 'Luisa',
        'last_name' => 'Bautista',
        'email' => 'luisa.bautista@tripwise.com',
        'department_id' => $this->adminDept->id,
        'position' => 'Admin Assistant',
        'employment_status' => 'regular',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0012345678',
        'is_active' => true,
    ]);
});

test('driver trip incentive service computes multi-tier bonuses based on completed trips', function () {
    $service = app(DriverTripIncentiveService::class);
    $cutoff = '2026-07-01_15';

    // 1. Below threshold (25 trips) -> 0 PHP
    TripIncome::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'total_trips' => 25,
        'total_trip_earnings' => 10000.00,
    ]);

    $res1 = $service->compute($this->driver, $cutoff);
    expect($res1['incentive_amount'])->toBe(0.00);

    // 2. Tier 1 (40 trips) -> 500 PHP
    TripIncome::where('employee_id', $this->driver->id)->update(['total_trips' => 40]);
    $res2 = $service->compute($this->driver, $cutoff);
    expect($res2['incentive_amount'])->toBe(500.00);

    // 3. Tier 2 (60 trips) -> 1,500 PHP
    TripIncome::where('employee_id', $this->driver->id)->update(['total_trips' => 60]);
    $res3 = $service->compute($this->driver, $cutoff);
    expect($res3['incentive_amount'])->toBe(1500.00);

    // 4. Tier 3 (80 trips) -> 3,000 PHP
    TripIncome::where('employee_id', $this->driver->id)->update(['total_trips' => 80]);
    $res4 = $service->compute($this->driver, $cutoff);
    expect($res4['incentive_amount'])->toBe(3000.00);

    // 5. Non-driver -> 0 PHP
    $resStaff = $service->compute($this->staff, $cutoff);
    expect($resStaff['incentive_amount'])->toBe(0.00);
});

test('loan amortization service correctly computes deductions and clamps to remaining balance', function () {
    $service = app(LoanAmortizationService::class);
    $cutoff = '2026-07-01_15';

    // Standard loan with full balance
    $loan = EmployeeLoan::create([
        'employee_id' => $this->staff->id,
        'loan_type' => 'sss_salary_loan',
        'reference_no' => 'SSS-SL-TEST-01',
        'principal_amount' => 10000.00,
        'total_amount_due' => 11000.00,
        'term_months' => 12,
        'semi_monthly_amortization' => 458.33,
        'total_paid' => 0.00,
        'remaining_balance' => 11000.00,
        'start_date' => '2026-01-01',
        'status' => 'active',
    ]);

    $res = $service->compute($this->staff, $cutoff);
    expect($res['total_loan_deduction'])->toBe(458.33);

    // Loan with balance smaller than amortization
    $loan->update(['remaining_balance' => 200.00]);
    $resClamped = $service->compute($this->staff, $cutoff);
    expect($resClamped['total_loan_deduction'])->toBe(200.00);
});

test('loan amortization applies deduction on release and marks loan fully paid upon zero balance', function () {
    $service = app(LoanAmortizationService::class);
    $cutoff = '2026-07-01_15';

    $loan = EmployeeLoan::create([
        'employee_id' => $this->staff->id,
        'loan_type' => 'hdmf_multi_purpose_loan',
        'reference_no' => 'HDMF-MPL-TEST-01',
        'principal_amount' => 5000.00,
        'total_amount_due' => 5500.00,
        'term_months' => 12,
        'semi_monthly_amortization' => 500.00,
        'total_paid' => 5000.00,
        'remaining_balance' => 500.00,
        'start_date' => '2026-01-01',
        'status' => 'active',
    ]);

    $comp = SalaryComputation::create([
        'employee_id' => $this->staff->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 13000.00,
        'trip_earnings' => 0.00,
        'driver_trip_incentive' => 0.00,
        'holiday_pay' => 0.00,
        'overtime_pay' => 0.00,
        'night_diff_pay' => 0.00,
        'performance_bonus' => 0.00,
        'reimbursements' => 0.00,
        'gross_pay' => 13000.00,
        'sss_deduction' => 650.00,
        'philhealth_deduction' => 325.00,
        'pagibig_deduction' => 100.00,
        'loan_deduction' => 500.00,
        'total_deductions' => 1575.00,
        'net_pay' => 11425.00,
        'status' => 'pending_approval',
    ]);

    $service->applyDeductions($comp);

    $loan->refresh();
    expect((float) $loan->remaining_balance)->toBe(0.00)
        ->and((float) $loan->total_paid)->toBe(5500.00)
        ->and($loan->status)->toBe('fully_paid');

    expect(LoanAmortizationLog::where('employee_loan_id', $loan->id)->count())->toBe(1);
});

test('payroll engine calculates driver trip quota incentives and loan deductions accurately', function () {
    $cutoff = '2026-07-01_15';

    TripIncome::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'total_trips' => 75, // Qualifies for Tier 3 (3,000 PHP)
        'total_trip_earnings' => 35000.00,
    ]);

    EmployeeLoan::create([
        'employee_id' => $this->driver->id,
        'loan_type' => 'company_emergency_loan',
        'reference_no' => 'PR-ADV-TEST-01',
        'principal_amount' => 5000.00,
        'total_amount_due' => 5000.00,
        'term_months' => 6,
        'semi_monthly_amortization' => 416.67,
        'total_paid' => 0.00,
        'remaining_balance' => 5000.00,
        'start_date' => '2026-07-01',
        'status' => 'active',
    ]);

    $engine = app(PayrollEngineService::class);
    $comp = $engine->computeForEmployee($this->driver, $cutoff);

    // Trip earnings: 35000, Incentive: 3000 -> Gross: 38000
    // Platform fee (20% of 35000): 7000, HMO (3% of 38000): 1140, Loan: 416.67
    expect((float) $comp->trip_earnings)->toBe(35000.00)
        ->and((float) $comp->driver_trip_incentive)->toBe(3000.00)
        ->and((float) $comp->gross_pay)->toBe(38000.00)
        ->and((float) $comp->loan_deduction)->toBe(416.67)
        ->and((float) $comp->net_pay)->toBeGreaterThan(0.00);
});

test('loan management web routes allow viewing registering and toggling loan statuses', function () {
    // 1. Index
    $res = $this->get(route('payroll.loans'));
    $res->assertOk();
    $res->assertViewIs('payroll-benefits.payroll.loans');

    // 2. Store
    $resStore = $this->post(route('payroll.loans.store'), [
        'employee_id' => $this->staff->id,
        'loan_type' => 'sss_salary_loan',
        'reference_no' => 'SSS-SL-WEB-001',
        'principal_amount' => 15000.00,
        'total_amount_due' => 16500.00,
        'term_months' => 24,
        'semi_monthly_amortization' => 343.75,
        'start_date' => '2026-08-01',
    ]);
    $resStore->assertRedirect();

    $loan = EmployeeLoan::where('reference_no', 'SSS-SL-WEB-001')->first();
    expect($loan)->not->toBeNull()
        ->and($loan->status)->toBe('active');

    // 3. Pause
    $resPause = $this->post(route('payroll.loans.pause', $loan->id));
    $resPause->assertRedirect();
    $loan->refresh();
    expect($loan->status)->toBe('paused');

    // 4. Resume
    $resResume = $this->post(route('payroll.loans.resume', $loan->id));
    $resResume->assertRedirect();
    $loan->refresh();
    expect($loan->status)->toBe('active');
});
