<?php

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceBonus;
use App\Services\Compensation\CompensationApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('store adjustment flags budget rejection if financial ceiling exceeded', function () {
    $department = Department::create(['name' => 'Operations']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-TEST-001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.com',
        'position' => 'Operations Manager',
        'monthly_rate' => 30000.00,
    ]);

    $response = $this->post(route('compensation.adjustments.store'), [
        'employee_id' => $employee->id,
        'type' => 'merit_promotion',
        'new_rate' => 200000.00, // Exceeds 150k limit in mock FinancialService
        'reason' => 'High increase test',
    ]);

    $response->assertSessionHas('error');

    $adjustment = CompensationAdjustment::where('employee_id', $employee->id)->first();
    expect($adjustment)->not->toBeNull()
        ->and($adjustment->status)->toBe('rejected_financial_budget');
});

test('active counter offer calculation route returns correct ctc and internal equity structure', function () {
    $grade = \App\Models\SalaryGrade::create([
        'grade_code' => 'PG-4',
        'job_level' => 'Supervisor',
        'position_name' => 'Operations Supervisor',
        'min_salary' => 30000.00,
        'max_salary' => 50000.00,
        'annual_growth_rate' => 7.00,
    ]);

    $response = $this->postJson(route('compensation.counter-offers.calculate'), [
        'mode' => 'mode_a',
        'salary_grade_id' => $grade->id,
        'competitor_offer' => 38000.00,
        'education' => 4,
        'experience' => 3,
        'skills' => 4,
        'market_benchmark' => 3,
        'internal_equity' => 3,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('mode', 'mode_a')
        ->assertJsonPath('grade_id', $grade->id)
        ->assertJsonStructure([
            'mode',
            'grade_id',
            'grade_code',
            'job_level',
            'position_name',
            'competitor_offer',
            'target_offer_cap',
            'max_counteroffer_cap',
            'proposed_base_salary',
            'exceeds_band_maximum',
            'determination',
            'ctc' => [
                'base_salary',
                'total_allowances',
                'employer_statutory' => ['sss', 'ec', 'philhealth', 'pagibig', 'total'],
                'monthly_ctc',
                'thirteenth_month_liability',
                'signing_bonus',
                'annual_ctc',
            ],
            'internal_equity' => [
                'status',
                'peer_count',
                'peer_median_salary',
                'variance_percentage',
                'message',
            ],
        ]);
});

test('legacy simulate growth route returns 404', function () {
    $response = $this->postJson('/compensation/api/simulate-growth', [
        'position' => 'Operations Manager',
        'years_experience' => 4,
    ]);

    $response->assertStatus(404);
});

test('team 3 merit promotion webhook ingests performance data and creates adjustment', function () {
    $department = Department::create(['name' => 'Operations']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-TEST-002',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@test.com',
        'position' => 'Operations Manager',
        'monthly_rate' => 30000.00,
    ]);

    $response = $this->postJson('/api/payroll/webhooks/merit-promotion', [
        'employee_id' => $employee->id,
        'kpi_score' => 96,
        'years_of_service' => 3,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.bonus_amount', 5000);

    $this->assertDatabaseHas('compensation_adjustments', [
        'employee_id' => $employee->id,
        'type' => 'merit_promotion',
        'new_rate' => 33000.00, // 10% bump for >= 90 KPI
    ]);
});

test('compensation approval service executes single authoritative payroll sync without duplicate writes', function () {
    $dept = Department::create(['name' => 'Operations']);
    $employee = Employee::create([
        'employee_code' => 'EMP-SYNC-TEST',
        'first_name' => 'Danilo',
        'last_name' => 'Ramos',
        'email' => 'danilo.ramos@test.com',
        'department_id' => $dept->id,
        'position' => 'Senior Operations Dispatcher',
        'employment_status' => 'regular',
        'monthly_rate' => 28000.00,
        'performance_rating' => 'Outstanding',
        'years_of_service' => 3.0,
        'hire_date' => now()->subYears(3),
    ]);

    $adjustment = CompensationAdjustment::create([
        'employee_id' => $employee->id,
        'subject_type' => 'employee',
        'type' => 'merit_promotion',
        'old_rate' => 28000.00,
        'new_rate' => 32000.00,
        'bonus_amount' => 5000.00,
        'old_position' => 'Senior Operations Dispatcher',
        'new_position' => 'Operations Supervisor',
        'status' => 'pending',
        'budget_impact_status' => 'BUDGET_APPROVED',
        'admin_approval_status' => 'ADMIN_APPROVED',
        'effective_date' => now(),
    ]);

    $service = app(CompensationApprovalService::class);
    $result = $service->finalizeAndSyncToPayroll($adjustment);

    expect($result)->toBeTrue();

    $employee->refresh();
    $adjustment->refresh();

    expect($adjustment->status)->toBe('approved')
        ->and((float) $employee->monthly_rate)->toBe(32000.00)
        ->and($employee->position)->toBe('Operations Supervisor');

    // Assert PerformanceBonus was created exactly once
    $bonuses = PerformanceBonus::where('employee_id', $employee->id)->get();
    expect($bonuses->count())->toBe(1)
        ->and((float) $bonuses->first()->bonus_amount)->toBe(5000.00);
});

