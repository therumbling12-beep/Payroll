<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;
use App\Services\Payroll\PayslipDistributionService;
use App\Services\Payroll\SecurityBankExportService;
use Database\Seeders\GovernmentContributionSeeder;
use Database\Seeders\HolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);
    $this->seed(HolidaySeeder::class);

    $this->dept = Department::create(['name' => 'Customer Experience']);

    $this->bankEmployee = Employee::create([
        'employee_code' => 'EMP-SBC-01',
        'first_name' => 'Marianne',
        'last_name' => 'Rivera',
        'email' => 'marianne.rivera@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Support Specialist',
        'employment_status' => 'regular',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0088776655',
        'hire_date' => '2025-01-15',
    ]);

    $this->cashEmployee = Employee::create([
        'employee_code' => 'EMP-CSH-02',
        'first_name' => 'Danilo',
        'last_name' => 'Bautista',
        'email' => 'danilo.bautista@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Utility Crew',
        'employment_status' => 'probationary',
        'monthly_rate' => 18000.00,
        'daily_rate' => 692.31,
        'payment_mode' => 'cash',
        'hire_date' => '2026-02-01',
    ]);
});

test('payslip distribution service formats transparent dole compliant earnings and deductions', function () {
    $cutoff = '2026-07-01_15';

    $loan = EmployeeLoan::create([
        'employee_id' => $this->bankEmployee->id,
        'loan_type' => 'sss_salary_loan',
        'reference_no' => 'SSS-SL-PAYSLIP-01',
        'principal_amount' => 12000.00,
        'total_amount_due' => 12000.00,
        'term_months' => 12,
        'semi_monthly_amortization' => 500.00,
        'total_paid' => 2000.00,
        'remaining_balance' => 10000.00,
        'start_date' => '2026-01-01',
        'status' => 'active',
    ]);

    $comp = SalaryComputation::create([
        'employee_id' => $this->bankEmployee->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 16000.00,
        'trip_earnings' => 0.00,
        'driver_trip_incentive' => 0.00,
        'holiday_pay' => 1500.00,
        'overtime_pay' => 1200.00,
        'night_diff_pay' => 300.00,
        'performance_bonus' => 1000.00,
        'reimbursements' => 500.00,
        'gross_pay' => 20000.00,
        'sss_deduction' => 800.00,
        'sss_employer' => 1600.00,
        'philhealth_deduction' => 400.00,
        'philhealth_employer' => 400.00,
        'pagibig_deduction' => 100.00,
        'pagibig_employer' => 100.00,
        'ec_contribution' => 10.00,
        'loan_deduction' => 500.00,
        'withholding_tax' => 1200.00,
        'tardiness_deduction' => 150.00,
        'undertime_deduction' => 50.00,
        'total_deductions' => 3200.00,
        'net_pay' => 17300.00,
        'status' => 'pending_approval',
    ]);

    $service = app(PayslipDistributionService::class);
    $data = $service->formatPayslipData($comp);

    expect($data['full_name'])->toBe('Rivera, Marianne')
        ->and($data['earnings']['gross_pay'])->toBe(20000.00)
        ->and($data['deductions']['total_deductions'])->toBe(3200.00)
        ->and($data['net_pay'])->toBe(17300.00)
        ->and($data['employer_contributions']['total_employer_burden'])->toBe(2110.00)
        ->and($data['itemized_loans'])->toHaveCount(1)
        ->and($data['itemized_loans'][0]['remaining_balance'])->toBe(10000.00);
});

test('security bank export service correctly segregates bank transfers and cash vouchers', function () {
    $cutoff = '2026-07-01_15';

    $compBank = SalaryComputation::create([
        'employee_id' => $this->bankEmployee->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 16000.00,
        'gross_pay' => 16000.00,
        'total_deductions' => 1500.00,
        'net_pay' => 14500.00,
        'status' => 'released_financial',
    ]);

    $compCash = SalaryComputation::create([
        'employee_id' => $this->cashEmployee->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 9000.00,
        'gross_pay' => 9000.00,
        'total_deductions' => 500.00,
        'net_pay' => 8500.00,
        'status' => 'released_financial',
    ]);

    $service = app(SecurityBankExportService::class);
    $allComps = collect([$compBank, $compCash]);

    // 1. SBC Bank CSV (Must only contain Marianne Rivera)
    $sbcCsv = $service->generateCsv($allComps, $cutoff);
    expect($sbcCsv)->toContain('MARIANNE RIVERA')
        ->and($sbcCsv)->toContain('0088776655')
        ->and($sbcCsv)->toContain('14500.00')
        ->and($sbcCsv)->not->toContain('DANILO BAUTISTA');

    // 2. Cash Voucher CSV (Must only contain Danilo Bautista)
    $cashCsv = $service->generateCashVoucherCsv($allComps, $cutoff);
    expect($cashCsv)->toContain('DANILO BAUTISTA')
        ->and($cashCsv)->toContain('8500.00')
        ->and($cashCsv)->toContain('VERIFIED BY CASHIER')
        ->and($cashCsv)->not->toContain('MARIANNE RIVERA');
});

test('payslip web routes render individual batch views and handle ess push', function () {
    $cutoff = '2026-07-01_15';

    $comp = SalaryComputation::create([
        'employee_id' => $this->bankEmployee->id,
        'cutoff_period' => $cutoff,
        'base_pay' => 16000.00,
        'gross_pay' => 16000.00,
        'total_deductions' => 1500.00,
        'net_pay' => 14500.00,
        'status' => 'released_financial',
    ]);

    // 1. Dashboard View
    $resIndex = $this->get(route('payroll.payslips', ['period' => $cutoff]));
    $resIndex->assertRedirect(route('payroll.salary-computation.show', $cutoff));

    // 2. Individual Printable View
    $resSingle = $this->get(route('payroll.payslips.show', $comp->id));
    $resSingle->assertOk();
    $resSingle->assertViewIs('payroll-benefits.payroll.payslip-printable');

    // 3. Batch Printable View
    $resBatch = $this->get(route('payroll.payslips.batch', $cutoff));
    $resBatch->assertOk();
    $resBatch->assertViewIs('payroll-benefits.payroll.payslips-batch-printable');

    // 4. Cash Voucher CSV Export
    $resCashExport = $this->get(route('payroll.export.cash-voucher', $cutoff));
    $resCashExport->assertOk();
    $resCashExport->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // 5. ESS Push Action
    $resEss = $this->post(route('payroll.payslips.push-ess', $cutoff));
    $resEss->assertRedirect();
    
    expect(PayrollAuditTrail::where('action', 'PUSH_PAYSLIPS_ESS')->count())->toBe(1);
});
