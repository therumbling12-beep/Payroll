<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Department;
use App\Models\Employee;
use App\Services\Claims\DriverMilestoneIncentiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create([
        'name' => 'Fleet Logistics',
        'code' => 'OPS-FLEET',
    ]);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-UNIT-01',
        'first_name' => 'Rodrigo',
        'last_name' => 'Dela Cruz',
        'email' => 'rodrigo.delacruz@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Chauffeur',
        'employment_status' => 'regular',
        'monthly_rate' => 0.00,
        'daily_rate' => 1000.00,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $this->hrDepartment = Department::create([
        'name' => 'Human Resources',
        'code' => 'HR-ADMIN',
    ]);

    $this->nonDriver = Employee::create([
        'employee_code' => 'EMP-UNIT-02',
        'first_name' => 'Carla',
        'last_name' => 'Reyes',
        'email' => 'carla.reyes@tripwise.com',
        'department_id' => $this->hrDepartment->id,
        'position' => 'HR Specialist',
        'employment_status' => 'regular',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $this->service = app(DriverMilestoneIncentiveService::class);
});

test('Driver completed rides under Tier 1 quota (<20 rides) does not qualify for milestone bonus', function () {
    $result = $this->service->computeDriverIncentive($this->driver, 15);

    expect($result['is_qualified'])->toBeFalse()
        ->and($result['qualified_tier'])->toBeNull()
        ->and($result['base_milestone_amount'])->toBe(0.00)
        ->and($result['total_incentive_amount'])->toBe(0.00)
        ->and($result['tier_label'])->toBe('Below Tier 1 Quota (<20 Rides)')
        ->and($result['next_tier'])->not->toBeNull()
        ->and($result['next_tier']['target_remaining'])->toBe(5);
});

test('Driver completed rides qualify for correct standard tier amount', function (int $rides, int $expectedTier, float $expectedBaseAmount, string $expectedLabel) {
    $result = $this->service->computeDriverIncentive($this->driver, $rides);

    expect($result['is_qualified'])->toBeTrue()
        ->and($result['qualified_tier'])->toBe($expectedTier)
        ->and((float) $result['base_milestone_amount'])->toBe($expectedBaseAmount)
        ->and((float) $result['total_incentive_amount'])->toBe($expectedBaseAmount)
        ->and($result['tier_label'])->toBe($expectedLabel);
})->with([
    'Tier 1 (25 rides)' => [25, 1, 500.00, 'Tier 1 (20 Rides)'],
    'Tier 2 (45 rides)' => [45, 2, 1000.00, 'Tier 2 (40 Rides)'],
    'Tier 3 (65 rides)' => [65, 3, 1500.00, 'Tier 3 (60 Rides)'],
    'Tier 4 (85 rides)' => [85, 4, 2500.00, 'Tier 4 (80 Rides)'],
    'Tier 5 (110 rides)' => [110, 5, 4000.00, 'Tier 5 (100+ Rides)'],
]);

test('Consistency and attendance bonuses are applied to qualifying Tier 2+ drivers', function () {
    // 50 rides qualifies for Tier 2 (₱1,000.00) + Consistency (₱500.00) + Attendance (₱500.00) = ₱2,000.00
    $result = $this->service->computeDriverIncentive(
        $this->driver,
        50,
        hasConsistency: true,
        hasAttendance: true
    );

    expect($result['is_qualified'])->toBeTrue()
        ->and($result['qualified_tier'])->toBe(2)
        ->and((float) $result['base_milestone_amount'])->toBe(1000.00)
        ->and((float) $result['consistency_bonus'])->toBe(500.00)
        ->and((float) $result['attendance_bonus'])->toBe(500.00)
        ->and((float) $result['total_incentive_amount'])->toBe(2000.00);
});

test('Consistency and attendance bonuses do not apply to Tier 1 drivers', function () {
    // 25 rides qualifies for Tier 1 (₱500.00), add-ons are suppressed
    $result = $this->service->computeDriverIncentive(
        $this->driver,
        25,
        hasConsistency: true,
        hasAttendance: true
    );

    expect($result['is_qualified'])->toBeTrue()
        ->and($result['qualified_tier'])->toBe(1)
        ->and((float) $result['base_milestone_amount'])->toBe(500.00)
        ->and((float) $result['consistency_bonus'])->toBe(0.00)
        ->and((float) $result['attendance_bonus'])->toBe(0.00)
        ->and((float) $result['total_incentive_amount'])->toBe(500.00);
});

test('Non-driver staff is barred from driver milestone incentives', function () {
    $result = $this->service->computeDriverIncentive($this->nonDriver, 50);

    expect($result['is_driver'])->toBeFalse()
        ->and($result['is_qualified'])->toBeFalse()
        ->and($result['total_incentive_amount'])->toBe(0.00);
});
