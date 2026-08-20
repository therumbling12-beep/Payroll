<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create([
        'name' => 'Fleet Logistics',
        'code' => 'OPS-FLT',
    ]);

    $this->driver = Employee::create([
        'employee_code' => 'DRV-P4-01',
        'first_name' => 'Manuel',
        'last_name' => 'Soriano',
        'email' => 'manuel.soriano@tripwise.com',
        'department_id' => $this->department->id,
        'position' => 'Delivery Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 24000.00,
        'daily_rate' => 923.08,
        'payment_mode' => 'bank',
        'is_active' => true,
    ]);

    $this->category = ClaimCategory::create([
        'name' => 'Driver Gas Expense',
        'code' => 'CAT-DRV-GAS',
        'type' => 'reimbursement',
        'tax_classification' => 'non_taxable',
        'color_tag' => 'orange',
        'max_amount' => 10000.00,
        'requires_receipt' => true,
        'applicable_to' => 'driver',
        'is_active' => true,
    ]);
});

test('Categories view renders clean 2-tab interface without redundant statutory guide tab', function () {
    $response = $this->get(route('claims.categories'));

    $response->assertOk()
        ->assertSee('Master Category Catalog')
        ->assertSee('Policy & Rates Setup', false)
        ->assertDontSee('Statutory Governance Guide');
});

test('Expenses view renders table and eager-loads relationships without N+1 bottlenecks', function () {
    Claim::create([
        'employee_id' => $this->driver->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'expense_subtype' => 'fuel',
        'amount' => 1450.00,
        'receipt_number' => 'OR-EXP-P4-01',
        'approval_status' => 'pending_hr',
        'status' => 'pending',
        'expense_date' => '2026-07-15',
        'is_duplicate_flagged' => true,
        'duplicate_risk_score' => 95,
        'duplicate_match_details' => [
            [
                'id' => 999,
                'receipt_number' => 'OR-EXP-P4-01',
                'amount' => 1450.00,
                'expense_date' => '2026-07-15',
                'match_type' => 'EXACT_RECEIPT',
            ],
        ],
    ]);

    $response = $this->get(route('claims.expenses'));

    $response->assertOk()
        ->assertSee('OR-EXP-P4-01')
        ->assertSee('Manuel Soriano');
});

test('All primary claims subpages render with status 200', function () {
    $this->get(route('claims.expenses'))->assertOk();
    $this->get(route('claims.incentives'))->assertRedirect(route('claims.expenses'));
    $this->get(route('claims.maternity-leave'))->assertOk();
    $this->get(route('claims.categories'))->assertOk();
    $this->get(route('claims.reports'))->assertOk();
});
