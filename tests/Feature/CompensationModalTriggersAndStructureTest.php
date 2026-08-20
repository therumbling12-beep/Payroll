<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CompanySetting::setValue('minimum_wage_daily', 755.00);
    $this->dept = Department::create(['name' => 'Logistics', 'code' => 'LOG']);
    $this->grade = SalaryGrade::firstOrCreate(
        ['grade_code' => 'PG-1'],
        [
            'position_name' => 'Fleet Driver',
            'job_level' => 'Entry Level',
            'min_salary' => 19630.00,
            'max_salary' => 28000.00,
            'annual_growth_rate' => 5.0,
        ]
    );
    $this->employee = Employee::create([
        'employee_code' => 'EMP-TEST-01',
        'first_name' => 'Danilo',
        'last_name' => 'Silang',
        'email' => 'danilo.s@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Driver',
        'daily_rate' => 800.00,
        'monthly_rate' => 20800.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('salary bands view contains valid modal triggers without broken quotes or premature closing divs', function () {
    $response = $this->get(route('compensation.salary-bands'));
    $response->assertOk();

    // Verify modal trigger directives are correctly bound with scalar values
    $response->assertSee('openCalculator(' . $this->grade->id . ')', false);
    $response->assertSee('openEdit(' . $this->grade->id, false);
    $response->assertSee('openDirectMerit(' . $this->employee->id, false);

    // Verify all 3 modals exist with their x-show expressions inside the template
    $response->assertSee('x-show="calcModalOpen"', false);
    $response->assertSee('x-show="editModalOpen"', false);
    $response->assertSee('x-show="meritModalOpen"', false);

    // Verify no broken unescaped object syntax exists in click attributes
    $response->assertDontSee('@click="openEdit({"', false);
    $response->assertDontSee('@click="openDirectMerit({"', false);
});
