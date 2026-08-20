<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('payment modes page renders clean registry table with icon action and without static policy tabs', function () {
    $dept = Department::firstOrCreate(['name' => 'Operations']);
    $employee = Employee::create([
        'employee_code' => 'EMP-1001',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripease.com',
        'department_id' => $dept->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0012345678',
        'hire_date' => now()->subYears(2),
    ]);

    $response = $this->get(route('payroll.payment-modes'));

    $response->assertOk();
    $response->assertSee('Maria Santos');
    $response->assertSee('Edit Disbursement Channel');
    $response->assertDontSee('Security Bank Payroll Facility</h2>', false);
    $response->assertDontSee('Physical Cash Payroll Envelope Preparation', false);
});
