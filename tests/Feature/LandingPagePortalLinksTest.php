<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Corporate Operations']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-PORTAL-01',
        'first_name' => 'Adrian',
        'last_name' => 'Castillo',
        'email' => 'adrian.castillo@tripease.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Systems Engineer',
        'monthly_rate' => 45000.00,
        'daily_rate' => 1730.77,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);
});

test('Landing page renders Employee Portal switchboard linking Team 4 to ESS dashboard', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee(route('ess.dashboard'))
        ->assertSee('Active Self-Service')
        ->assertSee('Launch Employee Self-Service (ESS)');
});

test('Admin Dashboard does not contain internal ESS sidebar link and renders clean HR console', function () {
    $user = \App\Models\User::factory()->create();
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertDontSee('ESS Self Service')
        ->assertSee('Team 4 - Payroll & Benefits');
});

test('Clicking Team 4 ESS route navigates directly to Employee Self-Service portal', function () {
    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $response->assertOk()
        ->assertSee('Adrian Castillo')
        ->assertSee('EMP-PORTAL-01')
        ->assertSee('Corporate Operations');
});
