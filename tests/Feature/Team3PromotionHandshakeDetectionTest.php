<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\SalaryGradeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SalaryGradeSeeder::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('progression desk automatically detects approved promotion order from team 3 and stages pay floor', function () {
    $dept = Department::firstOrCreate(['name' => 'HR']);
    $employee = Employee::create([
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

    // Team 3 records an approved promotion order in the shared database
    CompensationAdjustment::create([
        'employee_id' => $employee->id,
        'type' => 'merit_promotion',
        'status' => 'approved_by_team_3',
        'old_position' => 'HR Specialist',
        'new_position' => 'Senior HR Specialist',
        'old_rate' => 32000.00,
        'effective_date' => now(),
        'reason' => 'Official Team 3 Promotion Order #TM-2026-08',
    ]);

    $response = $this->get(route('compensation.merit-promotions'));

    $response->assertOk();
    $response->assertSee('Promoted:');
    $response->assertSee('Senior HR Specialist');
    $response->assertSee('Team 3 Promo Applied');
});
