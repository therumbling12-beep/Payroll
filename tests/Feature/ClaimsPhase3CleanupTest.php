<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create([
        'name' => 'General Operations',
        'code' => 'OPS-GEN',
    ]);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-P3-01',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Logistics Associate',
        'employment_status' => 'regular',
        'monthly_rate' => 25000.00,
        'daily_rate' => 961.54,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    ClaimCategory::create([
        'name' => 'Official Toll Fees',
        'code' => 'CAT-TOLL-01',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'max_amount' => 5000.00,
        'is_active' => true,
        'applicable_to' => 'driver',
        'description' => 'Expressway RFID and toll fees.',
    ]);
});

test('Categories page renders clean catalog and statutory guide without mock simulator', function () {
    $response = $this->get(route('claims.categories'));

    $response->assertOk()
        ->assertSee('Claim & Incentive Categories', false)
        ->assertSee('Official Toll Fees')
        ->assertSee('Statutory Governance Guide')
        ->assertDontSee('Interactive BIR TRAIN Law Taxability Simulator')
        ->assertDontSee('Recalculate Tax Breakdown');
});

test('Maternity leave claims page renders correctly with SSS tracking modal', function () {
    $response = $this->get(route('claims.maternity-leave'));

    $response->assertOk()
        ->assertSee('Maternity Leave & Benefit Claims', false)
        ->assertSee('SSS Recovery Milestone');
});

test('Reports and summary page renders correctly', function () {
    $response = $this->get(route('claims.reports'));

    $response->assertOk()
        ->assertSee('Claims Summary & Financial Audit Report', false)
        ->assertSee('Export Complete CSV Report');
});

test('Decommissioned archive route returns 404', function () {
    $response = $this->get('/claims/archive');
    expect($response->status())->toBe(404);
});

test('Decommissioned tax simulator endpoint returns 404 or method not allowed', function () {
    $response = $this->post('/claims/api/tax-classification-simulator', [
        'category_id' => 1,
        'amount' => 500.00,
    ]);

    expect($response->status())->toBe(404);
});
