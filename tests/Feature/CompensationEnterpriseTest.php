<?php

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
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

test('ajax simulation route returns correct counter offer calculation', function () {
    $response = $this->postJson(route('compensation.simulate'), [
        'position' => 'Operations Manager',
        'years_experience' => 4,
        'certifications_count' => 2,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['computed_counter_offer', 'financial_budget_check']]);
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
