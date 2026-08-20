<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('sidebar does not render driver ride incentives link', function () {
    $response = $this->get(route('claims.expenses'));

    $response->assertOk();
    $response->assertDontSee('Driver Ride Incentives');
    $response->assertSee('Driver Work Expenses');
    $response->assertSee('Maternity Leave Request');
});

test('legacy claims incentives route redirects gracefully to work expenses', function () {
    $response = $this->get('/claims/incentives');

    $response->assertRedirect(route('claims.expenses'));
});

test('salary computation tab 2 does not render trip quota incentives card', function () {
    $response = $this->get(route('payroll.salary-computation.show', '2026-08-01_15'));

    $response->assertOk();
    $response->assertDontSee('Driver Trip Quota Incentives');
    $response->assertDontSee('Tier 1 (30-49 Trips)');
});
