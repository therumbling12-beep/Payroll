<?php

declare(strict_types=1);

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
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

// BonusPoolDistributionService test removed — service deleted (Phase 5, docs/no.md: bonuses N/A)


test('POST /compensation/api/tenure-calculator and /probationary-calculator return 200 OK', function () {
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

    // 2. Probationary API decommissioned (Phase 2)
    $probRes = $this->postJson('/compensation/api/probationary-calculator', [
        'employee_id' => $this->probationaryEmp->id,
    ]);
    $probRes->assertNotFound();

    // 3. Bonus Pool API — removed (bonus-allocation route removed, Phase 2, docs/no.md: bonuses N/A)
});

test('salary step computes step_salary accessor correctly with explicit base_amount and dynamic fallback', function () {
    $grade = SalaryGrade::create([
        'grade_code' => 'PG-TEST-1',
        'job_level' => 'Entry',
        'position_name' => 'Test Fleet Assistant',
        'min_salary' => 20000.00,
        'max_salary' => 30000.00,
        'annual_growth_rate' => 5.00,
    ]);

    $stepWithBase = SalaryStep::create([
        'salary_grade_id' => $grade->id,
        'step_number' => 2,
        'years_required' => 3.0,
        'increment_percentage' => 5.0,
        'base_amount' => 22500.00,
    ]);

    expect($stepWithBase->step_salary)->toBe(22500.00)
        ->and($stepWithBase->salary_amount)->toBe(22500.00);

    $stepWithoutBase = SalaryStep::create([
        'salary_grade_id' => $grade->id,
        'step_number' => 3,
        'years_required' => 6.0,
        'increment_percentage' => 10.0,
        'base_amount' => null,
    ]);

    // 20,000 + (10% of 20,000) = 22,000.00
    expect($stepWithoutBase->step_salary)->toBe(22000.00)
        ->and($stepWithoutBase->salary_amount)->toBe(22000.00);
});
