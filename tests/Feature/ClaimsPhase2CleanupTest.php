<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TripIncome;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create([
        'name' => 'Fleet Operations',
        'code' => 'OPS-DRV',
    ]);

    $this->driver = Employee::create([
        'employee_code' => 'EMP-P2-01',
        'first_name' => 'Arman',
        'last_name' => 'Solis',
        'email' => 'arman.solis@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    TripIncome::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => '2026-07-01_15',
        'total_trips' => 65, // Qualifies for Tier 3 (₱1,500.00)
        'total_trip_earnings' => 13000.00,
    ]);
});

test('Driver ride incentives route redirects gracefully to work expenses', function () {
    $response = $this->get(route('claims.incentives'));

    $response->assertRedirect(route('claims.expenses'));
});

test('Batch qualify driver incentives route redirects gracefully to work expenses', function () {
    $response = $this->post(route('claims.incentives.batch-qualify'), [
        'cutoff_period' => '2026-07-01_15',
        'plans_json' => '[]',
    ]);

    $response->assertRedirect(route('claims.expenses'));
});

test('Decommissioned performance-incentive route returns 404', function () {
    $response = $this->get('/claims/performance-incentive');
    expect($response->status())->toBe(404);
});
