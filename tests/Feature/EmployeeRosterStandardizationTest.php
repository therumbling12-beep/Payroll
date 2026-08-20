<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('employee roster consists of strictly 15 official personnel without demo records', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $employees = Employee::all();
    expect($employees)->toHaveCount(15);

    expect(Employee::where('employee_code', 'EMP-DEMO-01')->exists())->toBeFalse();
    expect(Employee::where('employee_code', 'DRV-DEMO-02')->exists())->toBeFalse();

    // Verify 10 office staff + 5 drivers
    $driverCount = Employee::where('position', 'like', '%Driver%')->count();
    expect($driverCount)->toBe(5);
});

test('active weekly payroll folders reflect exactly 15 headcount', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $computations = SalaryComputation::where('cutoff_period', '2026-08-06_12')->get();
    expect($computations)->toHaveCount(15);

    $response = $this->get(route('payroll.salary-computation'));
    $response->assertOk();
    $response->assertSee('15 Headcount');
    $response->assertDontSee('17 Headcount');
});
