<?php

declare(strict_types=1);

use App\Models\SalaryComputation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('cutoffs list returns weekly thursday to wednesday folders when computations table is empty', function () {
    SalaryComputation::query()->delete();

    $response = $this->get(route('payroll.salary-computation'));

    $response->assertOk();
    $response->assertSee('2026-08-06_12');
    $response->assertSee('2026-08-13_19');
    $response->assertDontSee('01_15');
    $response->assertDontSee('16_31');
});
