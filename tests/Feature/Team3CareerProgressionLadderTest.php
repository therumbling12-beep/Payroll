<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('merit promotion desk presets official team 3 career ladder target grade for employees', function () {
    $dept = Department::firstOrCreate(['name' => 'Operations']);
    $driver = Employee::create([
        'employee_code' => 'EMP-1011',
        'first_name' => 'Eduardo',
        'last_name' => 'Santos',
        'email' => 'eduardo.santos@tripease.com',
        'department_id' => $dept->id,
        'position' => 'Driver',
        'monthly_rate' => 23400.00,
        'daily_rate' => 900.00,
        'hire_date' => now()->subYears(2),
    ]);

    $response = $this->get(route('compensation.merit-promotions'));

    $response->assertOk();
    $response->assertSee('Lead Fleet Driver');
    $response->assertSee('Team 3 Promotion Status');
});
