<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CompanySetting::setValue('minimum_wage_daily', 755.00);
    $this->dept = Department::create(['name' => 'Logistics', 'code' => 'LOG']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-UI-01',
        'first_name' => 'Teresa',
        'last_name' => 'Magbanua',
        'email' => 'teresa.m@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Driver',
        'daily_rate' => 850.00,
        'monthly_rate' => 22100.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('phase 3: salary bands view renders flexible compensation guideline and 6-factor candidate calculator', function () {
    $response = $this->get(route('compensation.salary-bands'));
    $response->assertOk();

    // Verify flexible banner
    $response->assertSee('Flexible Compensation');
    $response->assertSee('755.00');

    // Verify 6-factor calculator labels
    $response->assertSee('Relevant Experience', false);
    $response->assertSee('Technical & Job Skills', false);
    $response->assertSee('Educational Attainment', false);
    $response->assertSee('Professional Certifications', false);
    $response->assertSee('Previous Salary Benchmark', false);
    $response->assertSee('Interview Assessment', false);

    // Verify direct merit increase button
    $response->assertSee('Direct Merit');
});
