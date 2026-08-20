<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use App\Services\Compensation\CounterOfferService;
use App\Services\Compensation\ProbationaryConversionService;
use App\Services\Compensation\SalaryDeterminationService;
use App\Services\Compensation\TenureProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('salary determination service updates salary band and logs audit trail', function () {
    $grade = SalaryGrade::create([
        'position_name' => 'Fleet Supervisor',
        'min_salary' => 28000.00,
        'max_salary' => 45000.00,
        'annual_growth_rate' => 7.00,
    ]);

    $service = app(SalaryDeterminationService::class);
    $result = $service->updateSalaryBand($grade, 30000.00, 50000.00, 7.50, '2026-01-01');

    expect($result['success'])->toBeTrue()
        ->and($result['grade']->min_salary)->toBe(30000.00)
        ->and($result['grade']->max_salary)->toBe(50000.00);

    $this->assertDatabaseHas('payroll_audit_trails', [
        'action' => 'SALARY_BAND_UPDATE',
        'model_id' => $grade->id,
    ]);
});

test('salary determination service bulk adjusts bands proportionally', function () {
    SalaryGrade::create([
        'position_name' => 'Admin Assistant',
        'min_salary' => 15000.00,
        'max_salary' => 22000.00,
        'annual_growth_rate' => 5.50,
    ]);

    $service = app(SalaryDeterminationService::class);
    $result = $service->bulkAdjustBands(10.0);

    expect($result['success'])->toBeTrue()
        ->and($result['updated_grades_count'])->toBeGreaterThanOrEqual(1);

    $grade = SalaryGrade::where('position_name', 'Admin Assistant')->first();
    expect($grade->min_salary)->toBe(16500.00)
        ->and($grade->max_salary)->toBe(24200.00);
});

test('probationary conversion service generates categorized overview with milestone groups', function () {
    $dept = Department::create(['name' => 'Logistics']);

    Employee::create([
        'department_id' => $dept->id,
        'employee_code' => 'EMP-PBO-01',
        'first_name' => 'Rina',
        'last_name' => 'Cruz',
        'email' => 'rina.cruz@test.com',
        'position' => 'Logistics Associate',
        'employment_status' => 'probationary',
        'monthly_rate' => 22000.00,
        'hire_date' => now()->subMonths(5)->subDays(25), // 5 days remaining to 180
    ]);

    $service = app(ProbationaryConversionService::class);
    $overview = $service->getProbationaryOverview();

    expect($overview)->toHaveKeys(['total_probationary', 'critical_7_days', 'due_30_days', 'review_60_days', 'on_track', 'employees'])
        ->and($overview['total_probationary'])->toBeGreaterThanOrEqual(1)
        ->and(count($overview['critical_7_days']))->toBeGreaterThanOrEqual(1);
});

test('tenure progression service generates tenure overview with candidates', function () {
    $dept = Department::create(['name' => 'Operations']);

    $grade = SalaryGrade::create([
        'position_name' => 'Lead Operations Specialist',
        'min_salary' => 35000.00,
        'max_salary' => 55000.00,
        'annual_growth_rate' => 6.00,
    ]);

    SalaryStep::create([
        'salary_grade_id' => $grade->id,
        'step_number' => 1,
        'years_required' => 0.0,
        'increment_percentage' => 0.0,
        'base_amount' => 35000.00,
    ]);

    SalaryStep::create([
        'salary_grade_id' => $grade->id,
        'step_number' => 2,
        'years_required' => 3.0,
        'increment_percentage' => 5.0,
        'base_amount' => 36750.00,
    ]);

    Employee::create([
        'department_id' => $dept->id,
        'employee_code' => 'EMP-TNO-01',
        'first_name' => 'Eduardo',
        'last_name' => 'Santos',
        'email' => 'eduardo.santos@test.com',
        'position' => 'Lead Operations Specialist',
        'employment_status' => 'regular',
        'monthly_rate' => 35000.00,
        'current_step' => 1,
        'hire_date' => now()->subYears(4),
    ]);

    $service = app(TenureProgressionService::class);
    $overview = $service->getTenureStepOverview();

    expect($overview)->toHaveKeys(['salary_grades', 'candidates'])
        ->and(count($overview['candidates']))->toBeGreaterThanOrEqual(1);

    $candidate = collect($overview['candidates'])->firstWhere('employee.email', 'eduardo.santos@test.com');
    expect($candidate)->not->toBeNull()
        ->and($candidate['is_eligible'])->toBeTrue()
        ->and($candidate['target_step'])->toBe(2)
        ->and($candidate['projected_salary'])->toBe(36750.00);
});

test('api webhook counter offer computes mode a using dedicated counter offer service', function () {
    SalaryGrade::create([
        'position_name' => 'Senior Developer',
        'min_salary' => 45000.00,
        'max_salary' => 75000.00,
        'annual_growth_rate' => 8.00,
    ]);

    $response = $this->postJson('/api/payroll/webhooks/counter-offer', [
        'position' => 'Senior Developer',
        'years_experience' => 5,
        'certifications_count' => 2,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.mode', 'mode_a')
        ->assertJsonStructure(['message', 'data' => ['proposed_base_salary', 'ctc', 'internal_equity']]);
});
