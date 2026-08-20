<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('counter offers view does not render hmo tier select field', function () {
    $response = $this->get(route('compensation.counter-offers'));

    $response->assertOk();
    $response->assertDontSee('HMO Tier Entitlement');
    $response->assertDontSee('name="hmo_tier"', false);
});

test('payroll reports view does not display hmo deduction subtitle', function () {
    $response = $this->get(route('payroll.reports'));

    $response->assertOk();
    $response->assertDontSee('Gov\'t taxes, loans & HMO', false);
    $response->assertSee('Gov\'t taxes, loans & statutory deductions', false);
});

test('budget analytics and portal views do not render hmo labels', function () {
    $this->get(route('analytics.budget'))->assertOk()->assertDontSee('across HMO, Driver Claims');
    $this->get('/')->assertOk()->assertDontSee('digital HMO card');
});
