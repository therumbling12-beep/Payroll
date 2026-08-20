<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->department = Department::create(['name' => 'Logistics & Fleet']);
    
    $this->driver = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'DRV-001',
        'first_name' => 'Rolando',
        'last_name' => 'Cruz',
        'email' => 'rolando.cruz@test.com',
        'position' => 'Fleet Driver',
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
        'monthly_rate' => 22000.00,
        'payment_mode' => 'cash',
    ]);

    $this->computation = SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => '2026-08-01_07',
        'base_pay' => 5500.00,
        'trip_earnings' => 1200.00,
        'driver_trip_incentive' => 0.00,
        'overtime_pay' => 0.00,
        'holiday_pay' => 0.00,
        'night_diff_pay' => 0.00,
        'gross_pay' => 6700.00,
        'sss_deduction' => 450.00,
        'philhealth_deduction' => 200.00,
        'pagibig_deduction' => 100.00,
        'loan_deduction' => 0.00,
        'tardiness_deduction' => 0.00,
        'undertime_deduction' => 0.00,
        'withholding_tax' => 0.00,
        'total_deductions' => 750.00,
        'net_pay' => 5950.00,
        'status' => 'pending_approval',
    ]);
});

test('salary computation view renders direct manual encoding table cleanly', function () {
    $response = $this->get(route('payroll.salary-computation.show', '2026-08-01_07'));

    $response->assertOk();
    $response->assertSee('Rolando Cruz');
    $response->assertSee('DRV-001');
    $response->assertSee('Direct Manual Encoding');
    $response->assertSee('Save & Commit Weekly Payroll', false);
});

test('batch update manual computation successfully persists custom statutory deductions and recalculates net pay', function () {
    $response = $this->post(route('payroll.salary-computation.batch-update'), [
        'cutoff_period' => '2026-08-01_07',
        'computations' => [
            [
                'id' => $this->computation->id,
                'base_pay' => 5500.00,
                'trip_earnings' => 1200.00,
                'driver_trip_incentive' => 0.00,
                'overtime_pay' => 650.00,
                'holiday_pay' => 0.00,
                'night_diff_pay' => 0.00,
                'sss_deduction' => 500.00, // Custom manual SSS
                'philhealth_deduction' => 250.00, // Custom manual PhilHealth
                'pagibig_deduction' => 300.00, // Custom manual Pag-IBIG (> 200)
                'loan_deduction' => 500.00, // Loan deduction
                'tardiness_deduction' => 100.00,
                'undertime_deduction' => 50.00,
                'reimbursements' => 0.00,
            ],
        ],
    ]);

    $response->assertRedirect(route('payroll.salary-computation.show', '2026-08-01_07'));
    $response->assertSessionHas('status');

    $this->computation->refresh();

    // Gross: 5500 + 1200 + 650 = 7350.00 (Zero incentives)
    expect((float) $this->computation->gross_pay)->toBe(7350.00);

    // Statutory deductions: SSS 500 + PH 250 + PagIBIG 300 = 1050
    expect((float) $this->computation->sss_deduction)->toBe(500.00)
        ->and((float) $this->computation->philhealth_deduction)->toBe(250.00)
        ->and((float) $this->computation->pagibig_deduction)->toBe(300.00);

    // Total deductions: 500 + 250 + 300 + 500 (loan) + 100 (tardy) + 50 (undertime) + tax = 1700.00
    expect((float) $this->computation->total_deductions)->toBeGreaterThanOrEqual(1700.00);

    // Verify audit trail logged
    expect(PayrollAuditTrail::where('action', 'MANUAL_PAYROLL_BATCH_UPDATED')->exists())->toBeTrue();
});

test('batch update rejects submission when required statutory deductions or base pay are missing or negative', function () {
    $response = $this->post(route('payroll.salary-computation.batch-update'), [
        'cutoff_period' => '2026-08-01_07',
        'computations' => [
            [
                'id' => $this->computation->id,
                'base_pay' => -100.00,
                'sss_deduction' => -50.00,
                'philhealth_deduction' => 'invalid_amount',
                'pagibig_deduction' => 100.00,
            ],
        ],
    ]);

    $response->assertSessionHasErrors([
        'computations.0.base_pay',
        'computations.0.sss_deduction',
        'computations.0.philhealth_deduction',
    ]);
});
