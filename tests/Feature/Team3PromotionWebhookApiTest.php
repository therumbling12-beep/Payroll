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

test('team 3 promotion webhook api receives promotion order and creates staging adjustment', function () {
    $dept = Department::firstOrCreate(['name' => 'Dispatch & Routing']);
    $dispatcher = Employee::create([
        'employee_code' => 'EMP-1002',
        'first_name' => 'Jose',
        'last_name' => 'Reyes',
        'email' => 'jose.reyes@tripease.com',
        'department_id' => $dept->id,
        'position' => 'Operations Dispatcher',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
        'hire_date' => now()->subYears(2),
    ]);

    $payload = [
        'employee_code' => 'EMP-1002',
        'promoted_position' => 'Fleet Operations Lead',
        'target_grade_code' => 'PG-3',
        'promotion_order_number' => 'TM-PROMO-2026-009',
        'effective_date' => '2026-09-01',
        'reason' => 'Exceptional dispatch coverage leadership',
    ];

    $response = $this->postJson(route('api.integrations.team3.promotions'), $payload);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Team 3 promotion order successfully received and queued for payroll calibration',
            'employee_code' => 'EMP-1002',
            'promoted_position' => 'Fleet Operations Lead',
        ]);

    $this->assertDatabaseHas('compensation_adjustments', [
        'employee_id' => $dispatcher->id,
        'new_position' => 'Fleet Operations Lead',
        'status' => 'approved_by_team_3',
    ]);
});
