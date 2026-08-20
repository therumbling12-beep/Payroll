<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TripIncome;
use App\Services\Claims\DriverMilestoneIncentiveService;
use Database\Seeders\ClaimCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ClaimCategorySeeder::class);

    $this->fleetDept = Department::create(['name' => 'Fleet Operations']);
    $this->adminDept = Department::create(['name' => 'Administration & HR']);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-MIL-01',
        'first_name' => 'Rodrigo',
        'last_name' => 'Cruz',
        'email' => 'rodrigo.cruz@tripwise.com',
        'department_id' => $this->fleetDept->id,
        'position' => 'TNVS Senior Chauffeur',
        'monthly_rate' => 35000.00,
        'daily_rate' => 1346.15,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    $this->staff = Employee::create([
        'employee_code' => 'STF-MIL-02',
        'first_name' => 'Carla',
        'last_name' => 'Mendoza',
        'email' => 'carla.mendoza@tripwise.com',
        'department_id' => $this->adminDept->id,
        'position' => 'Accounting Analyst',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(1),
    ]);
});

test('computes standard tiered milestone bonuses', function () {
    $service = new DriverMilestoneIncentiveService();

    // 15 rides -> Below threshold
    $r15 = $service->computeDriverIncentive($this->driver, 15);
    expect($r15['is_qualified'])->toBeFalse()
        ->and($r15['total_incentive_amount'])->toBe(0.00)
        ->and($r15['qualified_tier'])->toBeNull();

    // 25 rides -> Tier 1 (PHP 500)
    $r25 = $service->computeDriverIncentive($this->driver, 25);
    expect($r25['is_qualified'])->toBeTrue()
        ->and($r25['qualified_tier'])->toBe(1)
        ->and($r25['total_incentive_amount'])->toBe(500.00);

    // 45 rides -> Tier 2 (PHP 1,000)
    $r45 = $service->computeDriverIncentive($this->driver, 45);
    expect($r45['qualified_tier'])->toBe(2)
        ->and($r45['total_incentive_amount'])->toBe(1000.00);

    // 65 rides -> Tier 3 (PHP 1,500)
    $r65 = $service->computeDriverIncentive($this->driver, 65);
    expect($r65['qualified_tier'])->toBe(3)
        ->and($r65['total_incentive_amount'])->toBe(1500.00);

    // 85 rides -> Tier 4 (PHP 2,500)
    $r85 = $service->computeDriverIncentive($this->driver, 85);
    expect($r85['qualified_tier'])->toBe(4)
        ->and($r85['total_incentive_amount'])->toBe(2500.00);

    // 105 rides -> Tier 5 (PHP 4,000)
    $r105 = $service->computeDriverIncentive($this->driver, 105);
    expect($r105['qualified_tier'])->toBe(5)
        ->and($r105['total_incentive_amount'])->toBe(4000.00);
});

test('adds consistency and attendance bonuses when driver meets qualification conditions', function () {
    $service = new DriverMilestoneIncentiveService();

    // 45 rides (Tier 2: 1000) + Consistency (+500) + Attendance (+500) = 2000
    $result = $service->computeDriverIncentive(
        $this->driver,
        45,
        hasConsistency: true,
        hasAttendance: true
    );

    expect($result['base_milestone_amount'])->toBe(1000.00)
        ->and($result['consistency_bonus'])->toBe(500.00)
        ->and($result['attendance_bonus'])->toBe(500.00)
        ->and($result['total_incentive_amount'])->toBe(2000.00);
});

test('rejects non-driver personnel from ride milestone bonus calculation', function () {
    $service = new DriverMilestoneIncentiveService();

    $result = $service->computeDriverIncentive($this->staff, 60);

    expect($result['is_driver'])->toBeFalse()
        ->and($result['is_qualified'])->toBeFalse()
        ->and($result['total_incentive_amount'])->toBe(0.00);
});

test('qualifyDriverRoster reads trip income records and qualifies drivers', function () {
    $service = new DriverMilestoneIncentiveService();
    $cutoff = '2026-07-01_15';

    TripIncome::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'total_trips' => 48,
        'gross_trip_fare' => 15000.00,
        'platform_commission' => 3000.00,
        'net_trip_income' => 12000.00,
    ]);

    $roster = $service->qualifyDriverRoster($cutoff);

    $driverRow = $roster->firstWhere('driver_id', $this->driver->id);
    expect($driverRow)->not->toBeNull()
        ->and($driverRow['completed_rides'])->toBe(48)
        ->and($driverRow['is_qualified'])->toBeTrue()
        ->and($driverRow['qualified_tier'])->toBe(2)
        ->and($driverRow['total_incentive_amount'])->toBe(1000.00);
});

test('POST /claims/incentives/batch-qualify redirects to work expenses', function () {
    $cutoff = '2026-07-01_15';

    $response = $this->post('/claims/incentives/batch-qualify', [
        'cutoff_period' => $cutoff,
    ]);

    $response->assertRedirect(route('claims.expenses'));
});

test('Decommissioned milestone calculator API endpoint returns 404', function () {
    $response = $this->postJson('/claims/api/milestone-calculator', [
        'driver_id' => $this->driver->id,
        'completed_rides' => 64,
    ]);

    expect($response->status())->toBe(404);
});
