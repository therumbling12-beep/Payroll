<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\ThirteenthMonthComputation;
use App\Services\Payroll\BirAlphalistService;
use App\Services\Payroll\MinimumWageGuardService;
use Database\Seeders\GovernmentContributionSeeder;
use Database\Seeders\HolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);
    $this->seed(HolidaySeeder::class);

    $this->dept = Department::create(['name' => 'Finance & Accounting']);

    $this->staff = Employee::create([
        'employee_code' => 'EMP-3001',
        'first_name' => 'Eduardo',
        'last_name' => 'Manalo',
        'email' => 'eduardo.manalo@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Accountant',
        'employment_status' => 'regular',
        'monthly_rate' => 40000.00,
        'daily_rate' => 1538.46,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0099887766',
        'is_active' => true,
    ]);
});

test('bir alphalist service computes annual gross compensation non taxable deductions and train tax', function () {
    $year = 2026;

    // Seed 2 semi-monthly cutoffs
    SalaryComputation::create([
        'employee_id' => $this->staff->id,
        'cutoff_period' => '2026-07-01_15',
        'base_pay' => 20000.00,
        'gross_pay' => 20000.00,
        'sss_deduction' => 900.00,
        'philhealth_deduction' => 500.00,
        'pagibig_deduction' => 100.00,
        'withholding_tax' => 1500.00,
        'total_deductions' => 3000.00,
        'net_pay' => 17000.00,
        'status' => 'pending_approval',
    ]);

    SalaryComputation::create([
        'employee_id' => $this->staff->id,
        'cutoff_period' => '2026-07-16_31',
        'base_pay' => 20000.00,
        'gross_pay' => 20000.00,
        'sss_deduction' => 900.00,
        'philhealth_deduction' => 500.00,
        'pagibig_deduction' => 100.00,
        'withholding_tax' => 1500.00,
        'total_deductions' => 3000.00,
        'net_pay' => 17000.00,
        'status' => 'pending_approval',
    ]);

    ThirteenthMonthComputation::create([
        'employee_id' => $this->staff->id,
        'year' => $year,
        'monthly_salary' => 40000.00,
        'months_worked' => 12,
        'amount' => 40000.00,
        'status' => 'approved',
    ]);

    $service = app(BirAlphalistService::class);
    $result = $service->computeAlphalist($year);

    expect($result['year'])->toBe($year)
        ->and($result['total_gross_compensation'])->toBeGreaterThan(0.00)
        ->and($result['total_non_taxable_statutory'])->toBe(3000.00)
        ->and($result['total_exempt_thirteenth_month'])->toBe(40000.00)
        ->and($result['employees'])->toHaveCount(1);

    $empRow = $result['employees'][0];
    expect($empRow['full_name'])->toBe('Manalo, Eduardo')
        ->and($empRow['tax_withheld'])->toBe(3000.00);
});

test('minimum wage guard service identifies compliant employees vs statutory ncr wage floor', function () {
    // Under-minimum employee (e.g. 500 PHP daily rate < 755 PHP statutory)
    $underMin = Employee::create([
        'employee_code' => 'EMP-LOW-01',
        'first_name' => 'Pedro',
        'last_name' => 'Penduko',
        'email' => 'pedro.penduko@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Utility Assistant',
        'employment_status' => 'probationary',
        'monthly_rate' => 12000.00,
        'daily_rate' => 500.00,
        'payment_mode' => 'cash',
        'is_active' => true,
    ]);

    $service = app(MinimumWageGuardService::class);
    $result = $service->evaluateCompliance();

    expect($result['statutory_daily_rate'])->toBe(755.00)
        ->and($result['is_fully_compliant'])->toBeFalse()
        ->and($result['non_compliant_count'])->toBeGreaterThanOrEqual(1);

    $pedroEval = collect($result['evaluations'])->firstWhere('employee_id', $underMin->id);
    expect($pedroEval)->not->toBeNull()
        ->and($pedroEval['is_compliant'])->toBeFalse()
        ->and($pedroEval['variance'])->toBe(-255.00);

    $eduardoEval = collect($result['evaluations'])->firstWhere('employee_id', $this->staff->id);
    expect($eduardoEval)->not->toBeNull()
        ->and($eduardoEval['is_compliant'])->toBeTrue();
});

test('statutory reports dashboard and csv export endpoints return valid responses', function () {
    $cutoff = '2026-07-01_15';
    $year = 2026;

    SalaryComputation::create([
        'employee_id' => $this->staff->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 20000.00,
        'gross_pay' => 20000.00,
        'sss_deduction' => 900.00,
        'sss_employer' => 1800.00,
        'philhealth_deduction' => 500.00,
        'philhealth_employer' => 500.00,
        'pagibig_deduction' => 100.00,
        'pagibig_employer' => 100.00,
        'ec_contribution' => 10.00,
        'withholding_tax' => 1500.00,
        'total_deductions' => 3000.00,
        'net_pay' => 17000.00,
        'status' => 'pending_approval',
    ]);

    // 1. Reports Dashboard View
    $resReports = $this->get(route('payroll.reports', ['period' => $cutoff, 'year' => $year]));
    $resReports->assertOk();
    $resReports->assertViewIs('payroll-benefits.payroll.reports');

    // 2. SSS R-3 Export
    $resSss = $this->get(route('payroll.export.sss', $cutoff));
    $resSss->assertOk();
    $resSss->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // 3. PhilHealth RF-1 Export
    $resPhil = $this->get(route('payroll.export.philhealth', $cutoff));
    $resPhil->assertOk();
    $resPhil->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // 4. Pag-IBIG MCRF Export
    $resPagibig = $this->get(route('payroll.export.pagibig', $cutoff));
    $resPagibig->assertOk();
    $resPagibig->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // 5. BIR 1604-C Alphalist Export
    $resAlpha = $this->get(route('payroll.export.alphalist', $year));
    $resAlpha->assertOk();
    $resAlpha->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
