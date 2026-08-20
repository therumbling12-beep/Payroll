<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('cutoff calendar displays weekly thursday to wednesday cycles from august to october 2026', function () {
    $response = $this->get(route('payroll.salary-computation'));

    $response->assertOk();
    $response->assertSee('Weekly Standard Active');
    $response->assertSee('2026-08-06_12');
    $response->assertSee('2026-08-13_19');
    $response->assertSee('2026-09-03_09');
    $response->assertSee('2026-10-01_07');
    $response->assertSee('Thu–Wed');
    $response->assertDontSee('Semi-Monthly Standard Active');
});

test('batch computation modal includes weekly thursday to wednesday options', function () {
    $response = $this->get(route('payroll.salary-computation'));

    $response->assertOk();
    $response->assertSee('value="2026-08-06_12"', false);
    $response->assertSee('value="2026-08-13_19"', false);
    $response->assertSee('value="2026-09-03_09"', false);
    $response->assertSee('value="2026-10-01_07"', false);
});
