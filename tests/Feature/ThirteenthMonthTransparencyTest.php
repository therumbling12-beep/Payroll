<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Services\Payroll\ThirteenthMonthService;
use Database\Seeders\GovernmentContributionSeeder;
use Database\Seeders\HolidaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);
    $this->seed(HolidaySeeder::class);

    $this->dept = Department::create(['name' => 'Finance & Accounting']);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-13TH-01',
        'first_name' => 'Catalina',
        'last_name' => 'Mendoza',
        'email' => 'catalina.mendoza@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Financial Controller',
        'employment_status' => 'regular',
        'monthly_rate' => 100000.00,
        'daily_rate' => 3846.15,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0011223344',
        'hire_date' => '2025-01-01',
    ]);
});

test('thirteenth month service generates 12 month cutoff matrix and train exemption split', function () {
    $year = 2026;

    // Seed 12 months with 50,000 PHP per cutoff (2 cutoffs per month = 100,000 PHP per month = 1,200,000 PHP annual base pay)
    for ($m = 1; $m <= 12; $m++) {
        $mStr = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
        
        SalaryComputation::create([
            'employee_id' => $this->employee->id,
            'cutoff_period' => "{$year}-{$mStr}-01_15",
            'base_pay' => 50000.00,
            'gross_pay' => 50000.00,
            'total_deductions' => 5000.00,
            'net_pay' => 45000.00,
            'status' => 'released_financial',
        ]);

        $lastDay = in_array($m, [4, 6, 9, 11]) ? '30' : ($m === 2 ? '28' : '31');
        SalaryComputation::create([
            'employee_id' => $this->employee->id,
            'cutoff_period' => "{$year}-{$mStr}-16_{$lastDay}",
            'base_pay' => 50000.00,
            'gross_pay' => 50000.00,
            'total_deductions' => 5000.00,
            'net_pay' => 45000.00,
            'status' => 'released_financial',
        ]);
    }

    $service = app(ThirteenthMonthService::class);
    $ledger = $service->getDetailedAuditLedger($year, $this->employee->id);

    // 1. 12-Month Matrix Verification
    expect($ledger['monthly_breakdown'])->toHaveCount(12)
        ->and($ledger['audit_metrics']['cutoffs_recorded_count'])->toBe(24)
        ->and($ledger['audit_metrics']['annual_base_pay_basis'])->toBe(1200000.00)
        ->and($ledger['audit_metrics']['calculated_amount'])->toBe(100000.00);

    // 2. TRAIN Law Exemption Split (PHP 100,000.00 -> PHP 90,000 exempt + PHP 10,000 taxable)
    expect($ledger['audit_metrics']['non_taxable_exempt'])->toBe(90000.00)
        ->and($ledger['audit_metrics']['taxable_excess'])->toBe(10000.00);

    // 3. DOLE Compliance Checks
    expect($ledger['dole_compliance']['min_service_requirement']['passed'])->toBeTrue()
        ->and($ledger['dole_compliance']['basis_restriction']['passed'])->toBeTrue();
});

test('thirteenth month transparency endpoint returns 200 json response with ledger payload', function () {
    $year = 2026;

    $response = $this->getJson(route('payroll.thirteenth-month.transparency', [
        'year' => $year,
        'employee' => $this->employee->id,
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'year',
        'employee' => ['id', 'code', 'name', 'position', 'department', 'hire_date', 'monthly_rate', 'hiring_status'],
        'audit_metrics' => [
            'months_worked',
            'cutoffs_recorded_count',
            'annual_base_pay_basis',
            'calculated_amount',
            'non_taxable_exempt',
            'taxable_excess',
            'computation_mode',
            'formula',
        ],
        'monthly_breakdown',
        'dole_compliance',
    ]);
});
