<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create(['name' => 'Fleet Operations']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-REP-01',
        'first_name' => 'Gabriel',
        'last_name' => 'Ramos',
        'email' => 'gabriel.ramos@example.com',
        'department_id' => $this->department->id,
        'position' => 'Fleet Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
    ]);

    $this->category = ClaimCategory::create([
        'code' => 'EXP-FUEL-99',
        'name' => 'Fuel Reimbursement',
        'type' => 'reimbursement',
        'taxability' => 'non_taxable',
        'max_amount_per_claim' => 5000.00,
        'is_active' => true,
    ]);
});

test('Claims consolidated report dashboard loads successfully with multi-stream metrics', function () {
    $approvedExpense = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'receipt_number' => 'REC-APP-EXP',
        'amount' => 2500.00,
        'non_taxable_amount' => 2500.00,
        'taxable_amount' => 0.00,
        'description' => 'Gasoline fuel approved',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    $pendingIncentive = Claim::create([
        'employee_id' => $this->employee->id,
        'type' => 'incentive',
        'receipt_number' => 'REC-PND-INC',
        'amount' => 1000.00,
        'non_taxable_amount' => 0.00,
        'taxable_amount' => 1000.00,
        'description' => 'Driver milestone incentive',
        'approval_status' => 'pending_hr',
        'status' => 'pending_hr',
    ]);

    $rejectedClaim = Claim::create([
        'employee_id' => $this->employee->id,
        'type' => 'expense',
        'receipt_number' => 'REC-REJ-EXP',
        'amount' => 500.00,
        'description' => 'Disallowed snack receipt',
        'approval_status' => 'rejected',
        'status' => 'rejected',
        'rejection_reason' => 'Snacks not covered under travel reimbursement policy.',
    ]);

    $response = $this->get(route('claims.reports'));

    $response->assertOk()
        ->assertSee('Claims Summary')
        ->assertSee('Financial Audit Report')
        ->assertSee('Total Disbursed Payout')
        ->assertSee('PHP 2,500.00')
        ->assertSee('Fleet Operations')
        ->assertSee('REC-APP-EXP')
        ->assertSee('REC-PND-INC')
        ->assertSee('REC-REJ-EXP')
        ->assertSee('Snacks not covered under travel reimbursement policy.');
});

test('Claims export endpoint streams downloadable CSV with comprehensive audit columns', function () {
    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'receipt_number' => 'REC-CSV-TEST',
        'amount' => 1750.50,
        'non_taxable_amount' => 1750.50,
        'taxable_amount' => 0.00,
        'description' => 'Highway Express tollway fee',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);

    $response = $this->get(route('claims.export', ['type' => 'expense']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // Capture streamed content
    ob_start();
    $response->sendContent();
    $csvContent = ob_get_clean();

    expect($csvContent)->toContain('Receipt Ref')
        ->and($csvContent)->toContain('REC-CSV-TEST')
        ->and($csvContent)->toContain('EMP-REP-01')
        ->and($csvContent)->toContain('Gabriel Ramos')
        ->and($csvContent)->toContain('Fleet Operations')
        ->and($csvContent)->toContain('1750.50');
});
