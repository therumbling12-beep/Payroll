<?php

declare(strict_types=1);

use App\Http\Resources\EmployeeResource;
use App\Http\Resources\SalaryComputationResource;
use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('employee resource transforms model attributes with strict types', function () {
    $department = Department::create(['name' => 'Human Resources', 'code' => 'HR']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-ARCH-001',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.doe@tripwise.test',
        'position' => 'HR Specialist',
        'monthly_rate' => 38000.00,
        'employment_status' => 'regular',
        'payment_mode' => 'bank',
        'bank_account_no' => '1234567890',
    ]);

    $resource = new EmployeeResource($employee);
    $array = $resource->toArray(Request::create('/api/employees/' . $employee->id));

    expect($array['id'])->toBe($employee->id)
        ->and($array['employee_code'])->toBe('EMP-ARCH-001')
        ->and($array['first_name'])->toBe('Jane')
        ->and($array['last_name'])->toBe('Doe')
        ->and($array['position'])->toBe('HR Specialist')
        ->and($array['department'])->toBe('Human Resources')
        ->and($array['payment_mode'])->toBe('bank')
        ->and($array['bank_account_no'])->toBe('1234567890');
});

test('salary computation resource transforms nested structure and numeric casts with strict types', function () {
    $department = Department::create(['name' => 'Fleet Logistics', 'code' => 'LOG']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-ARCH-002',
        'first_name' => 'Bob',
        'last_name' => 'Driver',
        'email' => 'bob.driver@tripwise.test',
        'position' => 'Express Chauffeur',
        'monthly_rate' => 28000.00,
        'employment_status' => 'regular',
        'payment_mode' => 'bank',
    ]);

    $computation = SalaryComputation::create([
        'employee_id' => $employee->id,
        'cutoff_period' => '2026-08-01_15',
        'base_pay' => 14000.00,
        'trip_earnings' => 6500.00,
        'performance_bonus' => 1500.00,
        'gross_pay' => 22000.00,
        'sss_deduction' => 1125.00,
        'philhealth_deduction' => 500.00,
        'pagibig_deduction' => 200.00,
        'total_deductions' => 1825.00,
        'net_pay' => 20175.00,
        'status' => 'pending_approval',
    ]);

    $resource = new SalaryComputationResource($computation);
    $array = $resource->toArray(Request::create('/api/payroll/' . $computation->id));

    expect($array['id'])->toBe($computation->id)
        ->and($array['cutoff_period'])->toBe('2026-08-01_15')
        ->and($array['earnings']['base_pay'])->toBe(14000.00)
        ->and($array['earnings']['trip_earnings'])->toBe(6500.00)
        ->and($array['earnings']['performance_bonus'])->toBe(1500.00)
        ->and($array['earnings']['gross_pay'])->toBe(22000.00)
        ->and($array['deductions']['sss'])->toBe(1125.00)
        ->and($array['deductions']['total_deductions'])->toBe(1825.00)
        ->and($array['net_pay'])->toBe(20175.00)
        ->and($array['status'])->toBe('pending_approval');
});

test('compensation adjustment observer logs audit trail entries on creation and update under strict types', function () {
    $department = Department::create(['name' => 'Operations', 'code' => 'OPS']);
    $employee = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EMP-ARCH-003',
        'first_name' => 'Carlos',
        'last_name' => 'Senior',
        'email' => 'carlos@tripwise.test',
        'position' => 'Fleet Supervisor',
        'monthly_rate' => 32000.00,
        'employment_status' => 'regular',
        'payment_mode' => 'bank',
    ]);

    // Create adjustment triggering created observer
    $adjustment = CompensationAdjustment::create([
        'employee_id' => $employee->id,
        'type' => 'merit_increase',
        'old_rate' => 32000.00,
        'new_rate' => 35000.00,
        'old_position' => 'Fleet Supervisor',
        'new_position' => 'Senior Fleet Supervisor',
        'status' => 'pending',
        'reason' => 'Annual performance merit promotion',
    ]);

    $createAudit = PayrollAuditTrail::where('model_id', $adjustment->id)
        ->where('action', 'COMPENSATION_PROPOSAL_CREATED')
        ->first();

    expect($createAudit)->not->toBeNull()
        ->and($createAudit->new_values)->toBeArray()
        ->and($createAudit->new_values['type'])->toBe('merit_increase');

    // Update adjustment status triggering updated observer
    $adjustment->update([
        'status' => 'approved',
    ]);

    $updateAudit = PayrollAuditTrail::where('model_id', $adjustment->id)
        ->where('action', 'COMPENSATION_APPROVED')
        ->first();

    expect($updateAudit)->not->toBeNull()
        ->and($updateAudit->old_values)->toBeArray()
        ->and($updateAudit->new_values)->toBeArray();
});
