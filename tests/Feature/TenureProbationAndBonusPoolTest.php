<?php

declare(strict_types=1);

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use App\Services\Compensation\BonusPoolDistributionService;
use App\Services\Compensation\CounterOfferService;
use App\Services\Compensation\ProbationaryConversionService;
use App\Services\Compensation\SalaryDeterminationService;
use App\Services\Compensation\TenureProgressionService;
use App\Services\FinancialService;
use Database\Seeders\SalaryGradeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SalaryGradeSeeder::class);

    $this->dept = Department::create(['name' => 'Field Operations']);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-TENURE-01',
        'first_name' => 'Arnel',
        'last_name' => 'Bautista',
        'email' => 'arnel.bautista@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Operations Dispatcher',
        'employment_status' => 'regular',
        'monthly_rate' => 28000.00,
        'current_step' => 1,
        'step_status' => 'normal',
        'performance_rating' => 'Very Satisfactory',
        'years_of_service' => 2.5,
        'hire_date' => now()->subYears(2)->subMonths(6),
    ]);

    $this->probationaryEmp = Employee::create([
        'employee_code' => 'EMP-PROB-01',
        'first_name' => 'Camille',
        'last_name' => 'Torres',
        'email' => 'camille.torres@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Operations Dispatcher',
        'employment_status' => 'probationary',
        'monthly_rate' => 20000.00,
        'current_step' => 1,
        'performance_rating' => 'Outstanding',
        'hire_date' => now()->subMonths(5)->subDays(15), // 165 days rendered
    ]);
});

test('TenureProgressionService computes step advances and handles step hold logic', function () {
    $counterOfferService = new CounterOfferService(new SalaryDeterminationService());
    $service = new TenureProgressionService($counterOfferService);

    // Compute Step 1 to Step 2
    $stepCalc = $service->computeNextStep($this->employee);
    expect($stepCalc['current_step'])->toBe(1)
        ->and($stepCalc['next_step'])->toBe(2)
        ->and($stepCalc['next_step_salary'])->toBeGreaterThan(28000.00)
        ->and($stepCalc['is_max_step'])->toBeFalse();

    // Apply Step Advance
    $applied = $service->applyStepAdvance($this->employee);
    expect($applied)->toBeTrue();

    $this->employee->refresh();
    expect($this->employee->current_step)->toBe(2)
        ->and($this->employee->monthly_rate)->toBeGreaterThan(28000.00)
        ->and($this->employee->step_status)->toBe('normal');

    // Hold Step Advance
    $held = $service->holdStepAdvance($this->employee, 'Performance review pending disciplinary clearance.');
    expect($held)->toBeTrue();

    $this->employee->refresh();
    expect($this->employee->step_status)->toBe('held')
        ->and($this->employee->step_hold_reason)->toContain('disciplinary clearance');
});

test('ProbationaryConversionService evaluates DOLE 6-month status and converts to regular status', function () {
    $counterOfferService = new CounterOfferService(new SalaryDeterminationService());
    $service = new ProbationaryConversionService($counterOfferService);

    // Evaluate probationary status
    $eval = $service->evaluateProbationaryStatus($this->probationaryEmp);
    expect($eval['milestone_reached'])->toBeTrue()
        ->and($eval['is_eligible'])->toBeTrue()
        ->and($eval['recommended_salary'])->toBeGreaterThanOrEqual(20000.00)
        ->and($eval['dole_compliance'])->toContain('180-Day Window');

    // Perform Regularization
    $regularized = $service->regularizeEmployee($this->probationaryEmp, 22000.00, 'Exemplary 6-month evaluation score.');
    expect($regularized)->toBeTrue();

    $this->probationaryEmp->refresh();
    expect($this->probationaryEmp->employment_status)->toBe('regular')
        ->and($this->probationaryEmp->regularization_date)->not->toBeNull()
        ->and((float) $this->probationaryEmp->monthly_rate)->toBe(22000.00)
        ->and($this->probationaryEmp->current_step)->toBe(2);

    $adj = CompensationAdjustment::where('employee_id', $this->probationaryEmp->id)
        ->where('type', 'probationary_conversion')
        ->first();
    expect($adj)->not->toBeNull()
        ->and($adj->new_rate)->toBe(22000.00)
        ->and($adj->status)->toBe('approved');
});

test('BonusPoolDistributionService distributes bonus pool proportionally without rounding leak', function () {
    $counterOfferService = new CounterOfferService(new SalaryDeterminationService());
    $financialService = new FinancialService();
    $service = new BonusPoolDistributionService($financialService, $counterOfferService);

    $poolAmount = 100000.00;
    $result = $service->calculateDistribution($poolAmount, $this->dept->id, 'performance');

    expect($result['pool_amount'])->toBe(100000.00)
        ->and($result['total_allocated'])->toBe(100000.00)
        ->and(count($result['allocations']))->toBeGreaterThan(0);

    // Commit Bonus Allocation
    $committed = $service->commitBonusAllocation($poolAmount, $this->dept->id, 'performance', $result['allocations']);
    expect($committed)->toBeTrue();

    $bonusAdjustments = CompensationAdjustment::where('type', 'performance_bonus')->get();
    expect($bonusAdjustments->count())->toBeGreaterThan(0);
});

test('POST /compensation/api/tenure-calculator, /probationary-calculator and /bonus-pool-calculator return 200 OK', function () {
    // 1. Tenure API
    $tenureRes = $this->postJson('/compensation/api/tenure-calculator', [
        'employee_id' => $this->employee->id,
    ]);
    $tenureRes->assertOk()
        ->assertJsonStructure([
            'employee_id',
            'current_step',
            'next_step',
            'current_salary',
            'next_step_salary',
            'ctc_impact',
            'formula',
        ]);

    // 2. Probationary API
    $probRes = $this->postJson('/compensation/api/probationary-calculator', [
        'employee_id' => $this->probationaryEmp->id,
    ]);
    $probRes->assertOk()
        ->assertJsonStructure([
            'employee_id',
            'days_rendered',
            'is_eligible',
            'recommended_salary',
            'dole_compliance',
        ]);

    // 3. Bonus Pool API
    $bonusRes = $this->postJson('/compensation/api/bonus-pool-calculator', [
        'pool_amount' => 50000.00,
        'department_id' => $this->dept->id,
        'bonus_type' => 'performance',
    ]);
    $bonusRes->assertOk()
        ->assertJsonStructure([
            'pool_amount',
            'total_allocated',
            'allocations',
            'budget_check',
        ]);
});
