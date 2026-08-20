<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('cutoff schedule calendar displays august 2026 through october 2026', function () {
    $response = $this->get(route('payroll.salary-computation'));

    $response->assertOk();
    $response->assertSee('August 2026');
    $response->assertSee('September 2026');
    $response->assertSee('October 2026');
    $response->assertSee('2026-08-06_12');
    $response->assertSee('2026-09-03_09');
    $response->assertSee('2026-10-01_07');
    $response->assertDontSee('January 2026');
    $response->assertDontSee('December 2026');
});

test('batch computation modal includes august to october 2026 cutoff options', function () {
    $response = $this->get(route('payroll.salary-computation'));

    $response->assertOk();
    $response->assertSee('value="2026-08-06_12"', false);
    $response->assertSee('value="2026-08-13_19"', false);
    $response->assertSee('value="2026-09-03_09"', false);
    $response->assertSee('value="2026-09-10_16"', false);
    $response->assertSee('value="2026-10-01_07"', false);
    $response->assertSee('value="2026-10-08_14"', false);
});
