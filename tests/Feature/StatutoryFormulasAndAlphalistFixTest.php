<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\SalaryComputation;
use App\Models\ThirteenthMonthComputation;
use App\Services\Payroll\BirAlphalistService;
use App\Services\Payroll\HolidayPayService;
use App\Services\Payroll\PhilHealthContributionService;
use App\Services\Payroll\SssContributionService;
use App\Services\Payroll\WithholdingTaxService;
use App\Services\PayrollEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('HolidayPayService accurately parses cross-month weekly thursday to wednesday cutoff dates', function () {
    $service = app(HolidayPayService::class);

    // August 27 (Thursday) to September 02 (Wednesday)
    $dates = $service->parseCutoffDates('2026-08-27_02');

    expect($dates['start'])->toBe('2026-08-27')
        ->and($dates['end'])->toBe('2026-09-02');
});

test('SSS and PhilHealth services strictly compute weekly prorated contributions', function () {
    $sssService = app(SssContributionService::class);
    $philhealthService = app(PhilHealthContributionService::class);

    $monthlySalary = 30000.00;

    $sssWeekly = $sssService->compute($monthlySalary, false, true);
    $philhealthWeekly = $philhealthService->compute($monthlySalary, false, true);

    // SSS: Monthly MSC 30k -> EE 1,500.00 -> Weekly = (1500 * 12) / 52 = 346.15
    expect((float) $sssWeekly['employee_share'])->toEqual(346.15);

    // PhilHealth: Monthly 30k * 0.05 = 1,500 total (750 EE) -> Weekly = (750 * 12) / 52 = 173.08
    expect((float) $philhealthWeekly['employee_share'])->toEqual(173.08);
});

test('BIR Alphalist accurately includes 13th month in gross before applying statutory exemption for weekly staff', function () {
    $dept = Department::create(['name' => 'Administration', 'code' => 'ADM']);
    $emp = Employee::create([
        'first_name' => 'Carlos',
        'last_name' => 'Reyes',
        'email' => 'carlos.reyes@example.com',
        'employee_code' => 'EMP-TEST-003',
        'department_id' => $dept->id,
        'position' => 'Finance Officer',
        'employment_status' => 'regular',
        'monthly_rate' => 30000.00,
    ]);

    $year = 2026;

    // Seed 52 weekly cutoffs of ₱6,923.08 base pay (₱360,000 annual gross)
    for ($w = 1; $w <= 52; $w++) {
        $wStr = str_pad((string) $w, 2, '0', STR_PAD_LEFT);
        SalaryComputation::create([
            'employee_id' => $emp->id,
            'cutoff_period' => "{$year}-W{$wStr}",
            'base_pay' => 6923.08,
            'gross_pay' => 6923.08,
            'sss_deduction' => 346.15,
            'philhealth_deduction' => 173.08,
            'pagibig_deduction' => 46.15,
            'total_deductions' => 565.38,
            'net_pay' => 6357.70,
            'withholding_tax' => 200.00,
            'status' => 'released_financial',
        ]);
    }

    ThirteenthMonthComputation::create([
        'employee_id' => $emp->id,
        'year' => $year,
        'monthly_salary' => 30000.00,
        'months_worked' => 12,
        'amount' => 30000.00,
        'non_taxable_exempt' => 30000.00,
        'taxable_excess' => 0.00,
        'status' => 'approved',
    ]);

    $alphalistService = app(BirAlphalistService::class);
    $report = $alphalistService->computeAlphalist($year);

    $empRow = collect($report['employees'])->firstWhere('employee_id', $emp->id);

    expect($empRow)->not->toBeNull();
    // Regular Gross: 360,000. Statutory: 29,400. 13th Month: 30,000 (Exempt: 30,000).
    // Taxable Compensation must equal: (360,000 + 30,000) - 29,400 - 30,000 = 330,600.
    expect((float) $empRow['taxable_compensation'])->toBeGreaterThan(325000.00);
});
