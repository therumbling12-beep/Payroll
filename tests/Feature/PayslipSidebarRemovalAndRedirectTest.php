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

test('sidebar does not contain redundant standalone payslip generation link', function () {
    $response = $this->get(route('payroll.salary-computation'));

    $response->assertOk();
    $response->assertDontSee('Payslip Generation</a>', false);
    $response->assertSee('Salary Computation');
});

test('legacy payroll payslips route safely redirects to salary computation desk', function () {
    $response = $this->get(route('payroll.payslips'));

    $response->assertRedirect(route('payroll.salary-computation'));
});
