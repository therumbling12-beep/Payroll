<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceIncentiveLeave;
use App\Models\User;
use App\Services\Benefits\ServiceIncentiveLeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Operations', 'code' => 'OPS']);
    $this->user = User::factory()->create();

    // Senior Employee (2.5 years service - Entitled to SIL)
    $this->seniorEmp = Employee::create([
        'employee_code' => 'EMP-SIL-01',
        'first_name' => 'Carlos',
        'last_name' => 'Mendoza',
        'email' => 'carlos.m@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Senior Operations Specialist',
        'monthly_rate' => 26000.00,
        'daily_rate' => 1000.00,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2)->subMonths(6),
    ]);

    // Junior/Probationary Employee (4 months service - Not yet entitled)
    $this->juniorEmp = Employee::create([
        'employee_code' => 'EMP-SIL-02',
        'first_name' => 'Liza',
        'last_name' => 'Reyes',
        'email' => 'liza.r@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Junior Dispatcher',
        'monthly_rate' => 20800.00,
        'daily_rate' => 800.00,
        'employment_status' => 'probationary',
        'hire_date' => now()->subMonths(4),
    ]);
});

test('sil service computes DOLE Art. 95 entitlement based on 1 year tenure', function () {
    $service = app(ServiceIncentiveLeaveService::class);
    $year = (int) date('Y');

    $seniorEntitlement = $service->getOrCreateAnnualRecord($this->seniorEmp, $year);
    $juniorEntitlement = $service->getOrCreateAnnualRecord($this->juniorEmp, $year);

    expect($seniorEntitlement->entitled_days)->toBe(5)
        ->and($seniorEntitlement->remaining_days)->toBe(5)
        ->and($juniorEntitlement->entitled_days)->toBe(0)
        ->and($juniorEntitlement->remaining_days)->toBe(0);
});

test('sil page renders correctly with employee roster and stat cards', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('benefits.sil'));
    $response->assertOk()
        ->assertSee('Service Incentive Leave (SIL) Tracker')
        ->assertSee('Carlos Mendoza')
        ->assertSee('5 Days');
});

test('hr can record sil leave days and remaining balance decreases', function () {
    $this->actingAs($this->user);
    $year = (int) date('Y');

    $response = $this->post(route('benefits.sil.record'), [
        'employee_id' => $this->seniorEmp->id,
        'year' => $year,
        'days_taken' => 2,
        'leave_date' => now()->format('Y-m-d'),
        'notes' => 'Family emergency rest leave DOLE Art. 95',
    ]);

    $response->assertRedirect(route('benefits.sil'))
        ->assertSessionHas('status');

    $record = ServiceIncentiveLeave::where('employee_id', $this->seniorEmp->id)
        ->where('year', $year)
        ->first();

    expect($record->used_days)->toBe(2)
        ->and($record->remaining_days)->toBe(3)
        ->and($record->leave_logs)->toHaveCount(1)
        ->and($record->leave_logs[0]['days'])->toBe(2);
});

test('hr can convert unused sil balance to cash based on daily rate', function () {
    $this->actingAs($this->user);
    $year = (int) date('Y');

    $service = app(ServiceIncentiveLeaveService::class);
    $record = $service->getOrCreateAnnualRecord($this->seniorEmp, $year);
    $record->update(['used_days' => 2]);

    $response = $this->post(route('benefits.sil.convert-cash'), [
        'employee_id' => $this->seniorEmp->id,
        'year' => $year,
        'days_to_convert' => 3,
    ]);

    $response->assertRedirect(route('benefits.sil'))
        ->assertSessionHas('status');

    $record->refresh();
    expect($record->cash_converted_days)->toBe(3)
        ->and((float) $record->cash_converted_amount)->toBe(3000.00) // 3 days * 1000 daily rate
        ->and($record->remaining_days)->toBe(0)
        ->and($record->status)->toBe('converted');
});

test('hr can initialize annual sil rollover for upcoming year', function () {
    $this->actingAs($this->user);
    $nextYear = (int) date('Y') + 1;

    $response = $this->post(route('benefits.sil.reset-year'), [
        'target_year' => $nextYear,
    ]);

    $response->assertRedirect(route('benefits.sil', ['year' => $nextYear]))
        ->assertSessionHas('status');

    expect(ServiceIncentiveLeave::where('year', $nextYear)->count())->toBeGreaterThanOrEqual(1);
});

test('hr can export sil roster csv with utf8 headers', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('benefits.sil.export'));
    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertStreamed();
});

test('ess dashboard displays sil balance card for active employee', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->seniorEmp->id]));
    $response->assertOk()
        ->assertSee('Service Incentive Leave')
        ->assertSee('DOLE Art. 95')
        ->assertSee('5 Days');
});
