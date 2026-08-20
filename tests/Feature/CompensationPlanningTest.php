<?php

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use App\Services\Compensation\CounterOfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('salary band can be updated with effectivity date and logged to audit trail', function () {
    $grade = SalaryGrade::create([
        'position_name' => 'Fleet Supervisor',
        'min_salary' => 28000.00,
        'max_salary' => 45000.00,
        'annual_growth_rate' => 7.00,
    ]);

    $response = $this->post(route('compensation.salary-bands.update', $grade), [
        'min_salary' => 30000.00,
        'max_salary' => 50000.00,
        'annual_growth_rate' => 7.50,
        'effectivity_date' => '2026-01-01',
    ]);

    $response->assertRedirect();
    $grade->refresh();

    expect($grade->min_salary)->toBe(30000.00)
        ->and($grade->max_salary)->toBe(50000.00)
        ->and($grade->effectivity_date->format('Y-m-d'))->toBe('2026-01-01');

    $this->assertDatabaseHas('payroll_audit_trails', [
        'action' => 'SALARY_BAND_UPDATE',
        'model_id' => $grade->id,
    ]);
});

test('bulk market adjustment applies percentage increase across all salary grades', function () {
    SalaryGrade::create([
        'position_name' => 'Admin Assistant',
        'min_salary' => 15000.00,
        'max_salary' => 22000.00,
        'annual_growth_rate' => 5.50,
    ]);

    $response = $this->post(route('compensation.salary-bands.bulk-adjust'), [
        'percentage' => 10.0,
    ]);

    $response->assertRedirect();

    $grade = SalaryGrade::where('position_name', 'Admin Assistant')->first();
    expect($grade->min_salary)->toBe(16500.00)
        ->and($grade->max_salary)->toBe(24200.00);

    $this->assertDatabaseHas('payroll_audit_trails', [
        'action' => 'SALARY_BAND_BULK_ADJUST',
    ]);
});

test('counter offer calculation factors in education and competitor offer', function () {
    $grade = SalaryGrade::create([
        'position_name' => 'Dispatch & Routing Specialist',
        'min_salary' => 20000.00,
        'max_salary' => 32000.00,
        'annual_growth_rate' => 6.50,
    ]);

    $service = app(CounterOfferService::class);
    $result = $service->computeModeA(
        $grade,
        28000.00,
        [
            'education' => 4,
            'experience' => 3,
            'skills' => 4,
            'market_benchmark' => 3,
            'internal_equity' => 3,
        ]
    );

    expect($result['proposed_base_salary'])->toBeGreaterThanOrEqual(28000.00)
        ->and($result['proposed_base_salary'])->toBeLessThanOrEqual(32000.00)
        ->and($result['ctc']['annual_ctc'])->toBeGreaterThan($result['proposed_base_salary']);
});

test('employee response to counter offer can be updated', function () {
    $department = Department::create(['name' => 'Fleet']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-RESP-01',
        'first_name' => 'Ramon',
        'last_name' => 'Bautista',
        'email' => 'ramon.bautista@test.com',
        'position' => 'Fleet Driver (Senior & Junior)',
        'monthly_rate' => 22000.00,
    ]);

    $adjustment = CompensationAdjustment::create([
        'employee_id' => $employee->id,
        'subject_type' => 'employee',
        'type' => 'counter_offer',
        'old_rate' => 22000.00,
        'new_rate' => 26000.00,
        'status' => 'approved',
        'employee_response' => 'pending_response',
    ]);

    $response = $this->post(route('compensation.counter-offers.response', $adjustment), [
        'employee_response' => 'accepted',
    ]);

    $response->assertRedirect();
    $adjustment->refresh();
    expect($adjustment->employee_response)->toBe('accepted');
});

// bonus pool distribution test removed — bonus-allocation route removed (Phase 2, docs/no.md: bonuses N/A)

test('applying tenure step increment updates employee and logs compensation adjustment', function () {
    $department = Department::create(['name' => 'Logistics']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-STEP-001',
        'first_name' => 'Carlos',
        'last_name' => 'Santos',
        'email' => 'carlos.santos@test.com',
        'position' => 'Fleet Driver (Senior & Junior)',
        'monthly_rate' => 20000.00,
        'current_step' => 1,
    ]);

    $response = $this->post(route('compensation.tenure-steps.apply', $employee), [
        'target_step' => 2,
        'new_rate' => 21200.00,
        'reason' => 'Completed Step 2 requirements',
    ]);

    $response->assertRedirect();
    $employee->refresh();

    expect($employee->current_step)->toBe(2)
        ->and($employee->monthly_rate)->toBe(21200.00);

    $this->assertDatabaseHas('compensation_adjustments', [
        'employee_id' => $employee->id,
        'new_rate' => 21200.00,
        'status' => 'approved',
    ]);
});

test('probationary standalone routes are decommissioned while conversion service remains operational', function () {
    expect(\Illuminate\Support\Facades\Route::has('compensation.probationary'))->toBeFalse()
        ->and(\Illuminate\Support\Facades\Route::has('compensation.probationary.regularize'))->toBeFalse()
        ->and(\Illuminate\Support\Facades\Route::has('compensation.probationary.calculate'))->toBeFalse();

    $department = Department::create(['name' => 'Operations']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-PROB-001',
        'first_name' => 'Alex',
        'last_name' => 'Cruz',
        'email' => 'alex.cruz@test.com',
        'position' => 'Operations Dispatcher',
        'employment_status' => 'probationary',
        'hire_date' => now()->subMonths(6),
        'monthly_rate' => 22000.00,
    ]);

    $service = app(\App\Services\Compensation\ProbationaryConversionService::class);
    $service->regularizeEmployee($employee, 25000.00, 'Completed 6 months probation with high merit');

    $employee->refresh();

    expect($employee->employment_status)->toBe('regular')
        ->and($employee->monthly_rate)->toBe(25000.00)
        ->and($employee->regularization_date)->not->toBeNull();
});

test('audit trail logs can be filtered and exported to csv', function () {
    PayrollAuditTrail::create([
        'user_name' => 'HR Admin',
        'action' => 'COMPENSATION_APPROVED',
        'model_type' => 'CompensationAdjustment',
        'model_id' => 99,
        'new_values' => ['status' => 'approved'],
        'ip_address' => '127.0.0.1',
    ]);

    $viewResponse = $this->get(route('compensation.audit-trail', ['action' => 'COMPENSATION_APPROVED']));
    $viewResponse->assertOk();
    $viewResponse->assertSee('COMPENSATION_APPROVED');

    $exportResponse = $this->get(route('compensation.audit-trail.export', ['action' => 'COMPENSATION_APPROVED']));
    $exportResponse->assertOk();
    expect($exportResponse->headers->get('content-type'))->toContain('text/csv');
});

test('all compensation subpage view routes render successfully without error', function () {
    $grade = SalaryGrade::create([
        'position_name' => 'Utility & Janitor',
        'min_salary' => 13000.00,
        'max_salary' => 18000.00,
        'annual_growth_rate' => 5.0,
    ]);

    SalaryStep::create([
        'salary_grade_id' => $grade->id,
        'step_number' => 2,
        'years_required' => 3,
        'increment_percentage' => 5.0,
        'base_amount' => 13650.00,
    ]);

    $department = Department::create(['name' => 'Logistics']);
    Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-TEST-001',
        'first_name' => 'Juan',
        'last_name' => 'Luna',
        'email' => 'juan.luna@test.com',
        'position' => 'Utility & Janitor',
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(4),
        'monthly_rate' => 15000.00,
        'current_step' => 1,
    ]);

    Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-TEST-002',
        'first_name' => 'Maria',
        'last_name' => 'Clara',
        'email' => 'maria.clara@test.com',
        'position' => 'Utility & Janitor',
        'employment_status' => 'probationary',
        'hire_date' => now()->subMonths(3),
        'monthly_rate' => 13000.00,
    ]);

    $this->get(route('compensation.salary-bands'))->assertOk();
    $this->get(route('compensation.counter-offers'))->assertOk();
    $this->get(route('compensation.merit-promotions'))->assertOk();
    // bonus-allocation route removed (Phase 2)
    $this->get(route('compensation.tenure-steps'))->assertOk();
    // probationary route decommissioned (Phase 2)
    $this->get(route('compensation.audit-trail'))->assertOk();
});
