<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\Payroll\SecurityBankExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->department = Department::create(['name' => 'Fleet Logistics', 'code' => 'FLT']);
});

test('hr can update employee payment mode from cash to security bank with valid account number', function () {
    $employee = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-SBC-01',
        'first_name' => 'Eduardo',
        'last_name' => 'Manalo',
        'email' => 'eduardo.m@tripwise.com',
        'position' => 'Senior Driver',
        'payment_mode' => 'cash',
        'monthly_rate' => 28000.00,
    ]);

    $response = $this->actingAs($this->user)->post(route('payroll.payment-modes.update', $employee->id), [
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '0012-3456-7890',
    ]);

    $response->assertRedirect(route('payroll.payment-modes'));
    $employee->refresh();

    expect($employee->payment_mode)->toBe('bank')
        ->and($employee->bank_name)->toBe('Security Bank Corporation')
        ->and($employee->bank_account_number)->toBe('0012-3456-7890');
});

test('payment mode update rejects invalid security bank account number formats', function () {
    $employee = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-SBC-02',
        'first_name' => 'Grace',
        'last_name' => 'Tan',
        'email' => 'grace.tan@tripwise.com',
        'position' => 'Dispatcher',
        'payment_mode' => 'cash',
        'monthly_rate' => 24000.00,
    ]);

    $response = $this->actingAs($this->user)->post(route('payroll.payment-modes.update', $employee->id), [
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => 'INVALID-ABC',
    ]);

    $response->assertSessionHasErrors('bank_account_number');
    $employee->refresh();
    expect($employee->payment_mode)->toBe('cash');
});

test('security bank export csv streams only banked employees with correct sbc header structure', function () {
    $bankEmp = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-BANK-01',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.dc@tripwise.com',
        'position' => 'Driver',
        'payment_mode' => 'bank',
        'bank_name' => 'Security Bank Corporation',
        'bank_account_number' => '001234567890',
        'monthly_rate' => 30000.00,
    ]);

    $cashEmp = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-CASH-01',
        'first_name' => 'Pedro',
        'last_name' => 'Penduko',
        'email' => 'pedro.p@tripwise.com',
        'position' => 'New Driver',
        'payment_mode' => 'cash',
        'monthly_rate' => 25000.00,
    ]);

    SalaryComputation::create([
        'employee_id' => $bankEmp->id,
        'cutoff_period' => '2026-08-13_19',
        'base_pay' => 6923.08,
        'gross_pay' => 6923.08,
        'total_deductions' => 500.00,
        'net_pay' => 6423.08,
        'status' => 'released_financial',
    ]);

    SalaryComputation::create([
        'employee_id' => $cashEmp->id,
        'cutoff_period' => '2026-08-13_19',
        'base_pay' => 5769.23,
        'gross_pay' => 5769.23,
        'total_deductions' => 400.00,
        'net_pay' => 5369.23,
        'status' => 'released_financial',
    ]);

    $response = $this->actingAs($this->user)->get(route('payroll.export.security-bank', '2026-08-13_19'));
    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('SEQ_NO,EMPLOYEE_ID,ACCOUNT_NAME,ACCOUNT_NUMBER,AMOUNT,REFERENCE_NUMBER,REMARKS')
        ->and($content)->toContain('JUAN DELA CRUZ')
        ->and($content)->toContain('001234567890')
        ->and($content)->not->toContain('PEDRO PENDUKO');
});

test('cash voucher export csv streams only unbanked personnel with voucher tracking codes', function () {
    $bankEmp = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-BANK-02',
        'first_name' => 'Arnel',
        'last_name' => 'Pineda',
        'email' => 'arnel.p@tripwise.com',
        'position' => 'Driver',
        'payment_mode' => 'bank',
        'bank_account_number' => '009876543210',
        'monthly_rate' => 30000.00,
    ]);

    $cashEmp = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-CASH-02',
        'first_name' => 'Rodrigo',
        'last_name' => 'Santos',
        'email' => 'rodrigo.s@tripwise.com',
        'position' => 'New Probationary Driver',
        'payment_mode' => 'cash',
        'monthly_rate' => 22000.00,
    ]);

    SalaryComputation::create([
        'employee_id' => $bankEmp->id,
        'cutoff_period' => '2026-08-13_19',
        'base_pay' => 6923.08,
        'gross_pay' => 6923.08,
        'total_deductions' => 500.00,
        'net_pay' => 6423.08,
        'status' => 'released_financial',
    ]);

    SalaryComputation::create([
        'employee_id' => $cashEmp->id,
        'cutoff_period' => '2026-08-13_19',
        'base_pay' => 5076.92,
        'gross_pay' => 5076.92,
        'total_deductions' => 350.00,
        'net_pay' => 4726.92,
        'status' => 'released_financial',
    ]);

    $response = $this->actingAs($this->user)->get(route('payroll.export.cash-voucher', '2026-08-13_19'));
    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('VOUCHER_NO,EMPLOYEE_CODE,EMPLOYEE_NAME,DEPARTMENT,GROSS_PAY,TOTAL_DEDUCTIONS,NET_CASH_DISBURSEMENT')
        ->and($content)->toContain('RODRIGO SANTOS')
        ->and($content)->toContain('4726.92')
        ->and($content)->not->toContain('ARNEL PINEDA');
});

test('cash denomination breakdown accurately computes bill units for net cash payouts', function () {
    $service = app(SecurityBankExportService::class);

    $computations = collect([
        (object) [
            'employee' => (object) ['payment_mode' => 'cash'],
            'net_pay' => 3870.00, // 3x1000 + 1x500 + 1x200 + 1x100 + 1x50 + 1x20
        ],
    ]);

    $breakdown = $service->calculateCashDenominationBreakdown($computations);

    expect($breakdown['denominations'][1000])->toBe(3)
        ->and($breakdown['denominations'][500])->toBe(1)
        ->and($breakdown['denominations'][200])->toBe(1)
        ->and($breakdown['denominations'][100])->toBe(1)
        ->and($breakdown['denominations'][50])->toBe(1)
        ->and($breakdown['denominations'][20])->toBe(1)
        ->and($breakdown['total_cash'])->toBe(3870.00);
});
