<?php

declare(strict_types=1);

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Services\Compensation\CounterOfferService;
use App\Services\Compensation\SalaryDeterminationService;
use Database\Seeders\SalaryGradeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SalaryGradeSeeder::class);

    $this->dept = Department::create(['name' => 'Operations Dispatch']);

    $this->seniorPeer = Employee::create([
        'employee_code' => 'EMP-PEER-01',
        'first_name' => 'Danilo',
        'last_name' => 'Ramos',
        'email' => 'danilo.ramos@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Operations Dispatcher',
        'employment_status' => 'regular',
        'monthly_rate' => 26000.00,
        'years_of_service' => 3.5,
        'hire_date' => now()->subYears(3),
    ]);
});

test('CounterOfferService calculates Mode A counter offers with statutory 1.10 cap and band maximum limit', function () {
    $service = new CounterOfferService(new SalaryDeterminationService());
    $grade = SalaryGrade::where('grade_code', 'PG-3')->first(); // PG-3: 20k to 30k

    // 1. Competitor offer of 24,000 -> 24k * 1.10 = 26,400 (under 30k max)
    $result = $service->computeModeA($grade, 24000.00, [
        'education' => 3,
        'experience' => 2,
        'skills' => 3,
        'market_benchmark' => 3,
        'internal_equity' => 3,
    ]);

    expect($result['mode'])->toBe('mode_a')
        ->and($result['competitor_offer'])->toBe(24000.00)
        ->and($result['target_offer_cap'])->toBe(26400.00)
        ->and($result['max_counteroffer_cap'])->toBe(26400.00)
        ->and($result['proposed_base_salary'])->toBe(26400.00)
        ->and($result['exceeds_band_maximum'])->toBeFalse();

    // 2. High Competitor offer of 32,000 -> 32k * 1.10 = 35,200 (capped at Grade Max 30,000)
    $highResult = $service->computeModeA($grade, 32000.00, [
        'education' => 3,
        'experience' => 3,
        'skills' => 3,
        'market_benchmark' => 3,
        'internal_equity' => 3,
    ]);

    expect($highResult['max_counteroffer_cap'])->toBe(30000.00)
        ->and($highResult['proposed_base_salary'])->toBe(30000.00)
        ->and($highResult['exceeds_band_maximum'])->toBeTrue();
});

test('CounterOfferService accurately calculates Total Cost to Company (CTC)', function () {
    $service = new CounterOfferService(new SalaryDeterminationService());

    $baseSalary = 30000.00;
    $allowances = 3500.00; // transport + meal
    $signingBonus = 5000.00;

    $ctc = $service->calculateTotalCostToCompany($baseSalary, $allowances, $signingBonus);

    // ER SSS for 30k MSC = 3,000.00, EC = 30.00, ER PhilHealth = 30k * 0.025 = 750.00, ER Pag-IBIG = 200.00
    // Total Statutory = 3000 + 30 + 750 + 200 = 3980.00
    // Monthly CTC = 30000 + 3500 + 3980 = 37480.00
    // Annual CTC = (37480 * 12) + 30000 (13th month) + 5000 (signing bonus) = 449760 + 30000 + 5000 = 484760.00

    expect($ctc['base_salary'])->toBe(30000.00)
        ->and($ctc['total_allowances'])->toBe(3500.00)
        ->and($ctc['employer_statutory']['total'])->toBe(3980.00)
        ->and($ctc['monthly_ctc'])->toBe(37480.00)
        ->and($ctc['thirteenth_month_liability'])->toBe(30000.00)
        ->and($ctc['annual_ctc'])->toBe(484760.00);
});

test('CounterOfferService evaluates internal equity and triggers wage distortion warning', function () {
    $service = new CounterOfferService(new SalaryDeterminationService());

    // Senior peer Danilo earns 26,000
    // If we propose 35,000 (> 26,000 * 1.15 = 29,900), it must trigger WAGE_DISTORTION_WARNING
    $distortionCheck = $service->evaluateInternalEquity('Operations Dispatcher', 35000.00);

    expect($distortionCheck['status'])->toBe('WAGE_DISTORTION_WARNING')
        ->and($distortionCheck['peer_median_salary'])->toBe(26000.00)
        ->and($distortionCheck['variance_percentage'])->toBeGreaterThan(15.0);

    // If we propose 26,500 (within 15% of peer median), status is NORMAL
    $normalCheck = $service->evaluateInternalEquity('Operations Dispatcher', 26500.00);
    expect($normalCheck['status'])->toBe('NORMAL');
});

test('POST /compensation/api/counter-offer-calculator returns structured CTC and wage distortion payload', function () {
    $grade = SalaryGrade::where('grade_code', 'PG-3')->first();

    $response = $this->postJson('/compensation/api/counter-offer-calculator', [
        'mode' => 'mode_a',
        'salary_grade_id' => $grade->id,
        'competitor_offer' => 25000.00,
        'education' => 3,
        'experience' => 2,
        'skills' => 3,
        'market_benchmark' => 3,
        'internal_equity' => 3,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'mode',
            'grade_id',
            'grade_code',
            'competitor_offer',
            'target_offer_cap',
            'max_counteroffer_cap',
            'proposed_base_salary',
            'ctc' => [
                'base_salary',
                'monthly_ctc',
                'annual_ctc',
                'thirteenth_month_liability',
            ],
            'internal_equity' => [
                'status',
                'peer_median_salary',
            ],
        ]);
});

test('POST /compensation/adjustments stores CTC and wage distortion metadata', function () {
    $grade = SalaryGrade::where('grade_code', 'PG-3')->first();

    $response = $this->post('/compensation/adjustments', [
        'type' => 'counter_offer',
        'mode' => 'mode_b',
        'subject_type' => 'applicant',
        'applicant_name' => 'Maria Victoria Reyes',
        'applicant_position' => 'Operations Dispatcher',
        'new_position' => 'Operations Dispatcher',
        'new_rate' => 28000.00,
        'transport_allowance' => 1500.00,
        'meal_allowance' => 1500.00,
        'signing_bonus' => 5000.00,
        'reason' => 'Candidate recruitment offer with itemized allowances and signing bonus.',
    ]);

    $response->assertRedirect();

    $adjustment = CompensationAdjustment::where('applicant_name', 'Maria Victoria Reyes')->first();
    expect($adjustment)->not->toBeNull()
        ->and($adjustment->new_rate)->toBe(28000.00)
        ->and($adjustment->monthly_ctc)->toBeGreaterThan(28000.00)
        ->and($adjustment->annual_ctc)->toBeGreaterThan(300000.00)
        ->and($adjustment->signing_bonus)->toBe(5000.00);
});
