<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\SalaryComputation;
use App\Services\Payroll\PayrollTransparencyService;
use Database\Seeders\GovernmentContributionSeeder;
use Database\Seeders\HolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);
    $this->seed(HolidaySeeder::class);

    $this->dept = Department::create(['name' => 'Operations & Fleet']);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-TRP-01',
        'first_name' => 'Danilo',
        'last_name' => 'Castro',
        'email' => 'danilo.castro@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior TNVS Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0099887766',
        'hire_date' => '2025-03-01',
    ]);
});

test('payroll transparency service generates exact mathematical formulas and statutory bracket lookups', function () {
    $cutoff = '2026-07-01_15';

    $loan = EmployeeLoan::create([
        'employee_id' => $this->driver->id,
        'loan_type' => 'company_emergency_loan',
        'reference_no' => 'ADV-TRANS-01',
        'principal_amount' => 8000.00,
        'total_amount_due' => 8000.00,
        'term_months' => 4,
        'semi_monthly_amortization' => 1000.00,
        'total_paid' => 2000.00,
        'remaining_balance' => 6000.00,
        'start_date' => '2026-05-01',
        'status' => 'active',
    ]);

    $computation = SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 14000.00,
        'trip_earnings' => 15000.00,
        'driver_trip_incentive' => 1500.00,
        'holiday_pay' => 2000.00,
        'overtime_pay' => 1500.00,
        'night_diff_pay' => 200.00,
        'performance_bonus' => 500.00,
        'reimbursements' => 300.00,
        'gross_pay' => 34700.00,
        'sss_deduction' => 800.00,
        'sss_employer' => 1600.00,
        'philhealth_deduction' => 400.00,
        'philhealth_employer' => 400.00,
        'pagibig_deduction' => 100.00,
        'pagibig_employer' => 100.00,
        'ec_contribution' => 10.00,
        'hmo_insurance_deduction' => 1041.00,
        'platform_fee_deduction' => 3000.00,
        'loan_deduction' => 1000.00,
        'withholding_tax' => 2500.00,
        'tardiness_deduction' => 100.00,
        'undertime_deduction' => 50.00,
        'total_deductions' => 8991.00,
        'net_pay' => 26009.00,
        'status' => 'pending_approval',
    ]);

    $service = app(PayrollTransparencyService::class);
    $breakdown = $service->generateBreakdown($computation);

    // 1. Base Pay Math Verification
    expect($breakdown['base_pay_math']['base_pay'])->toBe(14000.00)
        ->and($breakdown['base_pay_math']['formula'])->toContain('Daily Rate');

    // 2. TNVS Commission & Quota Verification
    expect($breakdown['tnvs_math']['trip_earnings'])->toBe(15000.00)
        ->and($breakdown['tnvs_math']['platform_fee_deduction'])->toBe(3000.00)
        ->and($breakdown['tnvs_math']['quota_tier_label'])->toContain('Tier 2');

    // 3. Statutory Lookups Verification
    expect($breakdown['statutory_lookups']['sss']['ee_share'])->toBe(800.00)
        ->and($breakdown['statutory_lookups']['sss']['table_reference'])->toContain('SSS Circular No. 2024-006')
        ->and($breakdown['statutory_lookups']['philhealth']['ee_share'])->toBe(400.00)
        ->and($breakdown['statutory_lookups']['pagibig']['ee_share'])->toBe(100.00);

    // 4. Loan Ledger Math
    expect($breakdown['loan_math'])->toHaveCount(1)
        ->and($breakdown['loan_math'][0]['balance_before'])->toBe(6000.00)
        ->and($breakdown['loan_math'][0]['amortization_deduction'])->toBe(1000.00)
        ->and($breakdown['loan_math'][0]['balance_after'])->toBe(5000.00);

    // 5. Employer Burden
    expect($breakdown['employer_burden']['total_company_burden'])->toBe(2110.00);
});

test('computation transparency endpoint returns 200 json response with complete mathematical payload', function () {
    $cutoff = '2026-07-01_15';

    $computation = SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 14000.00,
        'gross_pay' => 14000.00,
        'total_deductions' => 1300.00,
        'net_pay' => 12700.00,
        'status' => 'released_financial',
    ]);

    $response = $this->getJson(route('payroll.computations.transparency', $computation->id));

    $response->assertOk();
    $response->assertJsonStructure([
        'computation_id',
        'cutoff_period',
        'employee' => ['id', 'code', 'name', 'position', 'department', 'is_driver'],
        'base_pay_math' => ['monthly_rate', 'daily_rate', 'hourly_rate', 'minute_rate', 'days_rendered', 'base_pay', 'formula'],
        'attendance_math' => ['minute_rate', 'tardiness_minutes', 'tardiness_deduction', 'tardiness_formula'],
        'tnvs_math' => ['is_driver', 'trip_earnings', 'platform_fee_percent', 'platform_fee_deduction', 'quota_tier_label'],
        'statutory_lookups' => ['monthly_basis', 'sss', 'philhealth', 'pagibig', 'driver_hmo'],
        'tax_math' => ['gross_pay', 'taxable_income', 'train_bracket', 'withholding_tax', 'formula'],
        'loan_math',
        'net_pay_math' => ['gross_pay', 'total_deductions', 'reimbursements', 'net_pay', 'formula'],
        'employer_burden' => ['sss_employer', 'philhealth_employer', 'pagibig_employer', 'ec_contribution', 'total_company_burden', 'formula'],
    ]);
});
