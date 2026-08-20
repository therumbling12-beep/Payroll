<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('salary progression desk displays unified team 3 promotion upgrade column', function () {
    $dept = Department::firstOrCreate(['name' => 'HR']);
    Employee::create([
        'employee_code' => 'EMP-1001',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripease.com',
        'department_id' => $dept->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'hire_date' => now()->subYears(2),
    ]);

    $response = $this->get(route('compensation.merit-promotions'));

    $response->assertOk();
    $response->assertSee('Team 3 Promotion Status');
    $response->assertSee('Standard Grade Track');
    $response->assertSee('Raise Calibrator');
    $response->assertSee('Merit:');
    $response->assertSee('Step:');
});
