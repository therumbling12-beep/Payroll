<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('sidebar does not contain redundant payroll reports link under payroll management', function () {
    $response = $this->get(route('payroll.salary-computation'));

    $response->assertOk();
    $response->assertDontSee('Payroll Reports</a>', false);
    $response->assertSee('Salary Computation');
});

test('legacy payroll reports route renders reports desk for compliance exports', function () {
    $response = $this->get(route('payroll.reports'));

    $response->assertOk();
    $response->assertViewIs('payroll-benefits.payroll.reports');
});
