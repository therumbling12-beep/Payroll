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
        'employee_code' => 'EMP-TEST-99',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@example.com',
        'department_id' => $this->department->id,
        'position' => 'Senior Driver',
        'employment_status' => 'regular',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
    ]);

    $this->category = ClaimCategory::create([
        'code' => 'GAS-01',
        'name' => 'Fuel Expense',
        'type' => 'reimbursement',
        'taxability' => 'non_taxable',
        'max_amount_per_claim' => 5000.00,
        'is_active' => true,
    ]);
});

test('ESS dashboard displays claims tracker with readable stage labels and step badges', function () {
    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'expense_subtype' => 'fuel',
        'receipt_number' => 'REC-2026-999',
        'amount' => 2450.00,
        'description' => 'Gasoline top-up for airport route',
        'approval_status' => 'pending_hr',
        'status' => 'pending_hr',
        'expense_date' => now()->toDateString(),
    ]);

    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $response->assertOk()
        ->assertSee('My Claims')
        ->assertSee('Expense Reimbursement')
        ->assertSee('REC-2026-999')
        ->assertSee('PHP 2,450.00')
        ->assertSee('Gasoline top-up for airport route')
        ->assertSee('Pending HR Validation')
        ->assertSee('HR Review');
});

test('ESS dashboard displays In Payslip indicator when claim is queued or paid', function () {
    $claim = Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'incentive',
        'receipt_number' => 'INC-2026-888',
        'amount' => 1000.00,
        'description' => 'Target ride milestone completed',
        'approval_status' => 'payroll_queued',
        'status' => 'payroll_queued',
        'hr_approved_at' => now()->subDays(2),
        'finance_approved_at' => now()->subDay(),
        'payroll_queued_at' => now(),
        'cutoff_period' => '2026-08-01_15',
    ]);

    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $response->assertOk()
        ->assertSee('Ride Incentive')
        ->assertSee('INC-2026-888')
        ->assertSee('PHP 1,000.00')
        ->assertSee('Queued for Payroll')
        ->assertSee('In Payslip');
});

test('HR expense claims table renders with 4-step progress badges', function () {
    Claim::create([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'expense_subtype' => 'fuel',
        'receipt_number' => 'REC-2026-777',
        'amount' => 1500.00,
        'description' => 'Highway toll & fuel',
        'approval_status' => 'pending_hr',
        'status' => 'pending_hr',
        'expense_date' => now()->toDateString(),
    ]);

    $response = $this->get(route('claims.expenses'));

    $response->assertOk()
        ->assertSee('REC-2026-777')
        ->assertSee('1. Sub')
        ->assertSee('2. HR')
        ->assertSee('3. Fin')
        ->assertSee('4. Pay');
});
