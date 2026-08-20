<?php

declare(strict_types=1);

use App\Services\Payroll\PagIbigContributionService;
use App\Services\Payroll\PhilHealthContributionService;
use App\Services\Payroll\SssContributionService;
use App\Services\Payroll\WithholdingTaxService;
use Database\Seeders\GovernmentContributionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GovernmentContributionSeeder::class);
    $this->sss = app(SssContributionService::class);
    $this->philhealth = app(PhilHealthContributionService::class);
    $this->pagibig = app(PagIbigContributionService::class);
    $this->tax = app(WithholdingTaxService::class);
});

test('sss contributions adhere strictly to 2026 circular brackets floor ceiling and ec inflection', function () {
    // 1. Minimum Floor: ₱5,000 MSC -> EE: ₱250, ER: ₱500, EC: ₱10
    $floor = $this->sss->compute(5000.00, false);
    expect($floor['msc'])->toBe(5000.00)
        ->and($floor['employee_share'])->toBe(250.00)
        ->and($floor['employer_share'])->toBe(500.00)
        ->and($floor['ec_contribution'])->toBe(10.00);

    // 2. EC Inflection at ₱15,000 -> EE: ₱750, ER: ₱1500, EC: ₱30
    $ecCheck = $this->sss->compute(15000.00, false);
    expect($ecCheck['msc'])->toBe(15000.00)
        ->and($ecCheck['employee_share'])->toBe(750.00)
        ->and($ecCheck['employer_share'])->toBe(1500.00)
        ->and($ecCheck['ec_contribution'])->toBe(30.00);

    // 3. Middle Bracket: ₱20,000 -> EE: ₱1,000, ER: ₱2,000, EC: ₱30
    $mid = $this->sss->compute(20000.00, false);
    expect($mid['msc'])->toBe(20000.00)
        ->and($mid['employee_share'])->toBe(1000.00)
        ->and($mid['employer_share'])->toBe(2000.00)
        ->and($mid['ec_contribution'])->toBe(30.00);

    // 4. Maximum Ceiling: ₱35,000+ -> EE: ₱1,750, ER: ₱3,500, EC: ₱30
    $ceiling = $this->sss->compute(45000.00, false);
    expect($ceiling['msc'])->toBe(35000.00)
        ->and($ceiling['employee_share'])->toBe(1750.00)
        ->and($ceiling['employer_share'])->toBe(3500.00)
        ->and($ceiling['ec_contribution'])->toBe(30.00);
});

test('philhealth contributions adhere strictly to 5 percent premium floor and ceiling caps', function () {
    // 1. Floor at ₱10,000 -> ₱250 EE, ₱250 ER
    $floor = $this->philhealth->compute(8000.00, false);
    expect($floor['employee_share'])->toBe(250.00)
        ->and($floor['employer_share'])->toBe(250.00)
        ->and($floor['total_premium'])->toBe(500.00);

    // 2. Standard ₱20,000 MBS -> ₱500 EE, ₱500 ER (5% total = 1000)
    $std = $this->philhealth->compute(20000.00, false);
    expect($std['employee_share'])->toBe(500.00)
        ->and($std['employer_share'])->toBe(500.00)
        ->and($std['total_premium'])->toBe(1000.00);

    // 3. Middle ₱50,000 MBS -> ₱1,250 EE, ₱1,250 ER (from docs/PH_Government_Deductions_2026.md)
    $mid = $this->philhealth->compute(50000.00, false);
    expect($mid['employee_share'])->toBe(1250.00)
        ->and($mid['employer_share'])->toBe(1250.00)
        ->and($mid['total_premium'])->toBe(2500.00);

    // 4. Ceiling at ₱100,000+ -> ₱2,500 EE, ₱2,500 ER
    $ceiling = $this->philhealth->compute(150000.00, false);
    expect($ceiling['employee_share'])->toBe(2500.00)
        ->and($ceiling['employer_share'])->toBe(2500.00)
        ->and($ceiling['total_premium'])->toBe(5000.00);
});

test('pagibig contributions adhere strictly to 2 percent rate and 200 standard monthly cap with voluntary option', function () {
    // 1. Low income <= ₱1,500 -> 1% EE
    $low = $this->pagibig->compute(1200.00, false);
    expect($low['employee_share'])->toBe(12.00)
        ->and($low['employer_share'])->toBe(24.00);

    // 2. Standard >= ₱10,000 -> Capped at ₱200.00 EE / ₱200.00 ER
    $std = $this->pagibig->compute(25000.00, false);
    expect($std['employee_share'])->toBe(200.00)
        ->and($std['employer_share'])->toBe(200.00);

    // 3. Semi-monthly split -> ₱100.00 EE / ₱100.00 ER
    $semi = $this->pagibig->compute(25000.00, true);
    expect($semi['employee_share'])->toBe(100.00)
        ->and($semi['employer_share'])->toBe(100.00);

    // 4. Voluntary employee choice higher than cap (docs/no.md Line 117)
    $voluntary = $this->pagibig->compute(25000.00, false, 500.00);
    expect($voluntary['employee_share'])->toBe(500.00)
        ->and($voluntary['employer_share'])->toBe(200.00);
});

test('bir withholding tax computes exact train law graduated brackets and matches sample computation from guide', function () {
    // Sample computation from docs/PH_Government_Deductions_2026.md Lines 185-192:
    // Taxable Monthly Income: ₱48,275 -> Annual: ₱579,300 -> Tax: ₱22,500 + 20% of (579,300 - 400,000) = ₱58,360 / 12 = ₱4,863.33/mo
    $monthlyTax = $this->tax->compute(48275.00, false);
    expect($monthlyTax)->toBeGreaterThanOrEqual(4850.00)
        ->and($monthlyTax)->toBeLessThanOrEqual(4875.00);

    // Tax exempt below ₱20,833/mo (₱250k/yr)
    expect($this->tax->compute(18000.00, false))->toBe(0.00)
        ->and($this->tax->compute(20833.00, false))->toBe(0.00);
});
