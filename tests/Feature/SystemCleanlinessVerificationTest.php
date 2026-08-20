<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('system-wide audit: sidebar and canonical views do not contain driver quota incentives or hmo fields', function () {
    // 1. Claims Expenses Page
    $res = $this->get(route('claims.expenses'));
    $res->assertOk()
        ->assertDontSee('Driver Ride Incentives')
        ->assertSee('Driver Work Expenses');

    // 2. Counter Offers Page
    $res = $this->get(route('compensation.counter-offers'));
    $res->assertOk()
        ->assertDontSee('HMO Tier Entitlement')
        ->assertDontSee('name="hmo_tier"', false);

    // 3. Payroll Reports Page
    $res = $this->get(route('payroll.reports'));
    $res->assertOk()
        ->assertDontSee('Gov\'t taxes, loans & HMO', false)
        ->assertSee('Gov\'t taxes, loans & statutory deductions', false);

    // 4. Salary Computation Page
    $res = $this->get(route('payroll.salary-computation.show', '2026-08-01_15'));
    $res->assertOk()
        ->assertDontSee('trip quota incentives')
        ->assertDontSee('Tier 1 (30-49 Trips)');
});
