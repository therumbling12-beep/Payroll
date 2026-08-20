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

test('committing batch plan synchronizes promoted position and rate to employee profile and updates adjustment status', function () {
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

    $adjustment = CompensationAdjustment::create([
        'employee_id' => $employee->id,
        'type' => 'merit_promotion',
        'status' => 'approved_by_team_3',
        'old_position' => 'HR Specialist',
        'new_position' => 'Senior HR Specialist',
        'old_rate' => 32000.00,
        'new_rate' => 45000.00,
        'effective_date' => now(),
        'reason' => 'Team 3 Promotion Order #TM-2026-08',
    ]);

    $plans = [
        [
            'id' => $employee->id,
            'is_promoted' => true,
            'promoted_position' => 'Senior HR Specialist',
            'new_salary' => 45000.00,
            'raise_pct' => 40.6,
        ],
    ];

    $response = $this->post(route('compensation.merit-promotions.complete'), [
        'plans_json' => json_encode($plans),
    ]);

    $response->assertRedirect();
    $employee->refresh();
    expect($employee->position)->toBe('Senior HR Specialist')
        ->and((float)$employee->monthly_rate)->toBe(45000.00);

    $adjustment->refresh();
    expect($adjustment->status)->toBe('synced_to_payroll');
});
