<?php

declare(strict_types=1);

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\SalaryGrade;
use App\Services\Compensation\CounterOfferService;
use App\Services\Compensation\MeritIncreaseService;
use App\Services\Compensation\RetroactivePayCalculationService;
use App\Services\Compensation\SalaryDeterminationService;
use Database\Seeders\SalaryGradeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SalaryGradeSeeder::class);

    $this->dept = Department::create(['name' => 'Fleet Operations']);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-MERIT-01',
        'first_name' => 'Gabriel',
        'last_name' => 'Santos',
        'email' => 'gabriel.santos@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Dispatcher & Regular Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 25000.00,
        'performance_rating' => 'Outstanding',
        'years_of_service' => 2.0,
        'hire_date' => now()->subYears(2),
    ]);
});

test('MeritIncreaseService correctly computes 5-tier merit matrix increases and CTC impact', function () {
    $counterOfferService = new CounterOfferService(new SalaryDeterminationService());
    $service = new MeritIncreaseService($counterOfferService);

    // 1. Outstanding (5.0) -> 10.0% raise: 25,000 + 2,500 = 27,500
    $outstandingResult = $service->computeMeritIncrease($this->employee);
    expect($outstandingResult['applied_percentage'])->toBe(10.0)
        ->and($outstandingResult['increase_amount'])->toBe(2500.00)
        ->and($outstandingResult['proposed_salary'])->toBe(27500.00)
        ->and($outstandingResult['pip_triggered'])->toBeFalse();

    // 2. Needs Improvement (2.5) -> 1.0% raise: 25,000 + 250 = 25,250
    $this->employee->update(['performance_rating' => 'Needs Improvement']);
    $needsImpResult = $service->computeMeritIncrease($this->employee);
    expect($needsImpResult['applied_percentage'])->toBe(1.0)
        ->and($needsImpResult['increase_amount'])->toBe(250.00)
        ->and($needsImpResult['proposed_salary'])->toBe(25250.00)
        ->and($needsImpResult['pip_triggered'])->toBeFalse();

    // 3. Unsatisfactory (1.5) -> 0% raise + PIP triggered
    $this->employee->update(['performance_rating' => 'Unsatisfactory']);
    $unsatResult = $service->computeMeritIncrease($this->employee);
    expect($unsatResult['applied_percentage'])->toBe(0.0)
        ->and($unsatResult['increase_amount'])->toBe(0.00)
        ->and($unsatResult['proposed_salary'])->toBe(25000.00)
        ->and($unsatResult['pip_triggered'])->toBeTrue();
});

test('MeritIncreaseService calculates promotion advancement using 15% rule and new grade floor', function () {
    $counterOfferService = new CounterOfferService(new SalaryDeterminationService());
    $service = new MeritIncreaseService($counterOfferService);

    // Promote from current (25,000) to PG-4 (Senior Staff: Min 28,000 to Max 40,000)
    // 25,000 x 1.15 = 28,750 (> PG-4 Min 28,000), so promoted salary is 28,750
    $pg4 = SalaryGrade::where('grade_code', 'PG-4')->first();
    $promoResult = $service->computePromotion($this->employee, $pg4);

    expect($promoResult['fifteen_percent_floor'])->toBe(28750.00)
        ->and($promoResult['new_grade_min'])->toBe(28000.00)
        ->and($promoResult['promoted_salary'])->toBe(28750.00);

    // Promote to PG-5 (Supervisor: Min 38,000)
    // 25,000 x 1.15 = 28,750 (< PG-5 Min 38,000), so promoted salary steps up to new grade floor 38,000
    $pg5 = SalaryGrade::where('grade_code', 'PG-5')->first();
    $pg5Promo = $service->computePromotion($this->employee, $pg5);

    expect($pg5Promo['fifteen_percent_floor'])->toBe(28750.00)
        ->and($pg5Promo['new_grade_min'])->toBe(38000.00)
        ->and($pg5Promo['promoted_salary'])->toBe(38000.00);
});

test('RetroactivePayCalculationService computes daily differentials and injects into payroll computation', function () {
    $service = new RetroactivePayCalculationService();

    // Old monthly: 25,000 -> Daily: 25000/26 = 961.54
    // New monthly: 30,000 -> Daily: 30000/26 = 1153.85
    // Daily diff = 1153.85 - 961.54 = 192.31
    // 13 days = 192.31 * 13 = 2500.03
    $retro = $service->calculateRetroactiveDifferential($this->employee, 30000.00, '2026-08-01', 13);

    expect($retro['old_monthly_rate'])->toBe(25000.00)
        ->and($retro['new_monthly_rate'])->toBe(30000.00)
        ->and($retro['days_worked_prior'])->toBe(13)
        ->and($retro['daily_differential'])->toBeGreaterThan(190.00)
        ->and($retro['retroactive_pay'])->toBeGreaterThan(2400.00);

    // Test Injection to SalaryComputation
    $computation = SalaryComputation::create([
        'cutoff_period' => '2026-08-01 to 2026-08-15',
        'employee_id' => $this->employee->id,
        'base_pay' => 12500.00,
        'gross_pay' => 12500.00,
        'total_deductions' => 1500.00,
        'net_pay' => 11000.00,
        'status' => 'pending_approval',
    ]);

    $injected = $service->injectRetroactivePayToPayroll($this->employee, 2500.00, '2026-08-01 to 2026-08-15');
    expect($injected)->toBeTrue();

    $computation->refresh();
    expect((float) $computation->reimbursements)->toBe(2500.00)
        ->and((float) $computation->gross_pay)->toBe(15000.00)
        ->and((float) $computation->net_pay)->toBe(13500.00);
});

test('POST /compensation/api/merit-calculator and /api/retroactive-calculator return structured payloads', function () {
    $pg4 = SalaryGrade::where('grade_code', 'PG-4')->first();

    // 1. Merit API
    $meritRes = $this->postJson('/compensation/api/merit-calculator', [
        'employee_id' => $this->employee->id,
        'type' => 'promotion',
        'new_grade_id' => $pg4->id,
    ]);

    $meritRes->assertOk()
        ->assertJsonStructure([
            'employee_id',
            'old_position',
            'new_position',
            'promoted_salary',
            'ctc_impact' => [
                'current_monthly_ctc',
                'new_monthly_ctc',
                'incremental_annual_ctc',
            ],
            'formula',
        ]);

    // 2. Retroactive API
    $retroRes = $this->postJson('/compensation/api/retroactive-calculator', [
        'employee_id' => $this->employee->id,
        'new_monthly_rate' => 28000.00,
        'effective_date' => '2026-08-01',
        'days_worked' => 10,
    ]);

    $retroRes->assertOk()
        ->assertJsonStructure([
            'employee_id',
            'old_daily_rate',
            'new_daily_rate',
            'daily_differential',
            'retroactive_pay',
            'formula',
        ]);
});

test('POST /compensation/merit-promotions/complete approves batch and updates employee monthly rate', function () {
    $adjustment = CompensationAdjustment::create([
        'employee_id' => $this->employee->id,
        'type' => 'merit_promotion',
        'old_rate' => 25000.00,
        'new_rate' => 27500.00,
        'status' => 'pending',
        'reason' => 'Annual merit review based on Outstanding performance score.',
    ]);

    $response = $this->post('/compensation/merit-promotions/complete', [
        'adjustment_ids' => [$adjustment->id],
    ]);

    $response->assertRedirect();

    $adjustment->refresh();
    expect($adjustment->status)->toBe('approved')
        ->and($adjustment->budget_impact_status)->toBe('BUDGET_APPROVED');

    $this->employee->refresh();
    expect($this->employee->monthly_rate)->toBe(27500.00);
});

test('POST /compensation/merit-promotions/complete commits plans_json directly from Alpine state', function () {
    $simulatedPlans = [
        [
            'id' => $this->employee->id,
            'name' => $this->employee->first_name . ' ' . $this->employee->last_name,
            'current_salary' => 25000.00,
            'rating' => 'Outstanding',
            'raise_pct' => 10.0,
            'new_salary' => 27500.00,
            'monthly_ctc' => 31212.50,
            'annual_ctc' => 374550.00,
        ],
    ];

    $response = $this->post('/compensation/merit-promotions/complete', [
        'plans_json' => json_encode($simulatedPlans),
    ]);

    $response->assertRedirect();

    $this->employee->refresh();
    expect((float) $this->employee->monthly_rate)->toBe(27500.00)
        ->and((float) $this->employee->daily_rate)->toBe(round(27500.00 / 26, 2));

    $this->assertDatabaseHas('compensation_adjustments', [
        'employee_id' => $this->employee->id,
        'type' => 'merit_increase',
        'new_rate' => 27500.00,
        'status' => 'approved',
        'budget_impact_status' => 'BUDGET_APPROVED',
    ]);
});

