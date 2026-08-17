<?php

declare(strict_types=1);

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create(['name' => 'Fleet Operations']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-TEST-77',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@example.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
    ]);

    $this->category = ClaimCategory::create([
        'code' => 'GAS-02',
        'name' => 'Fuel Expense',
        'type' => 'reimbursement',
        'taxability' => 'non_taxable',
        'max_amount_per_claim' => 10000.00,
        'is_active' => true,
    ]);
});

test('Claim model correctly computes waiting days and identifies overdue status (> 3 days)', function () {
    $recentClaim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'receipt_number' => 'REC-REC-01',
        'amount' => 1000.00,
        'description' => 'Gasoline fuel',
        'approval_status' => 'pending_hr',
        'status' => 'pending_hr',
    ]);

    expect($recentClaim->waitingDays())->toBe(0)
        ->and($recentClaim->isOverdue())->toBeFalse()
        ->and($recentClaim->waiting_label)->toBe('Submitted Today');

    $overdueClaim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'receipt_number' => 'REC-OVD-01',
        'amount' => 2000.00,
        'description' => 'Highway toll',
        'approval_status' => 'pending_hr',
        'status' => 'pending_hr',
    ]);
    \Illuminate\Support\Facades\DB::table('claims')->where('id', $overdueClaim->id)->update(['created_at' => now()->subDays(5)]);
    $overdueClaim->refresh();

    expect($overdueClaim->waitingDays())->toBe(5)
        ->and($overdueClaim->isOverdue())->toBeTrue()
        ->and($overdueClaim->waiting_label)->toBe('Waiting 5 Days');

    $approvedClaim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'receipt_number' => 'REC-APP-01',
        'amount' => 1500.00,
        'description' => 'Maintenance',
        'approval_status' => 'approved',
        'status' => 'approved',
    ]);
    \Illuminate\Support\Facades\DB::table('claims')->where('id', $approvedClaim->id)->update(['created_at' => now()->subDays(10)]);
    $approvedClaim->refresh();

    expect($approvedClaim->isOverdue())->toBeFalse();
});

test('Partial amount approval adjusts claim amount and routes claim to next stage', function () {
    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'receipt_number' => 'REC-PARTIAL-01',
        'amount' => 3500.00,
        'non_taxable_amount' => 3500.00,
        'taxable_amount' => 0.00,
        'description' => 'Full tank gas and lubricants',
        'approval_status' => 'pending_hr',
        'status' => 'pending_hr',
        'expense_date' => now()->toDateString(),
    ]);

    $response = $this->post(route('claims.workflow-action', $claim->id), [
        'action' => 'approve_hr',
        'approved_amount' => 3000.00,
        'remarks' => 'Approved fuel portion only (lubricants not covered).',
    ]);

    $response->assertRedirect();

    $claim->refresh();

    expect((float) $claim->amount)->toBe(3000.00)
        ->and((float) $claim->non_taxable_amount)->toBe(3000.00)
        ->and($claim->approval_status)->toBe('pending_admin')
        ->and($claim->hr_remarks)->toContain('Approved Amount Adjusted: ₱3,000.00');
});

test('Aging filter (?aging=overdue) displays only claims waiting 3 or more days', function () {
    $recentClaim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'receipt_number' => 'REC-RECENT-01',
        'amount' => 1200.00,
        'description' => 'Recent claim submitted today',
        'approval_status' => 'pending_hr',
        'status' => 'pending_hr',
    ]);

    $overdueClaim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'receipt_number' => 'REC-OVERDUE-01',
        'amount' => 2800.00,
        'description' => 'Old claim submitted 4 days ago',
        'approval_status' => 'pending_hr',
        'status' => 'pending_hr',
    ]);
    \Illuminate\Support\Facades\DB::table('claims')->where('id', $overdueClaim->id)->update(['created_at' => now()->subDays(4)]);

    $response = $this->get(route('claims.expenses', ['aging' => 'overdue']));

    $response->assertOk()
        ->assertSee('REC-OVERDUE-01')
        ->assertSee('Overdue')
        ->assertDontSee('REC-RECENT-01');
});
