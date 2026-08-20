<?php

declare(strict_types=1);

use App\Models\AccidentClaim;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\DriverPoolLedger;
use App\Models\Employee;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\Benefits\DriverInsurancePoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Logistics Fleet', 'code' => 'LFT']);
    $this->user = User::factory()->create();

    $this->driver = Employee::create([
        'employee_code' => 'DRV-POOL-01',
        'first_name' => 'Nestor',
        'last_name' => 'Macaraeg',
        'email' => 'nestor.m@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Senior Delivery Driver',
        'monthly_rate' => 24000.00,
        'daily_rate' => 923.08,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    CompanySetting::setValue('driver_benefit_contribution_rate', '0.03'); // 3%
    CompanySetting::setValue('driver_pool_company_match_pct', '50.0'); // 50% match
});

test('driver pool service records automated contribution and company match from salary computation', function () {
    $cutoff = '2026-08-15_21';

    $computation = SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'gross_pay' => 12000.00,
        'trip_earnings' => 10000.00,
        'net_pay' => 9500.00,
        'status' => 'pending_approval',
    ]);

    $service = app(DriverInsurancePoolService::class);
    $entry = $service->recordPayrollContribution($computation);

    expect($entry)->not->toBeNull()
        ->and((float) $entry->amount)->toBe(300.00) // 3% of 10,000 trip earnings
        ->and($entry->entry_type)->toBe('driver_contribution');

    // Check company match
    $matchEntry = DriverPoolLedger::where('reference_code', 'like', "MATCH-{$cutoff}-%")->first();
    expect($matchEntry)->not->toBeNull()
        ->and((float) $matchEntry->amount)->toBe(150.00); // 50% of 300
});

test('payroll release automatically triggers driver insurance pool contribution ledger entries', function () {
    $this->actingAs($this->user);
    $cutoff = '2026-08-15_21';

    SalaryComputation::create([
        'employee_id' => $this->driver->id,
        'cutoff_period' => $cutoff,
        'gross_pay' => 10000.00,
        'trip_earnings' => 10000.00,
        'net_pay' => 8800.00,
        'status' => 'approved_legal',
    ]);

    $response = $this->post(route('payroll.workflow.release', ['cutoff' => $cutoff]));
    $response->assertRedirect();

    expect(DriverPoolLedger::where('employee_id', $this->driver->id)->count())->toBeGreaterThanOrEqual(1);
});

test('hr can view individual driver contribution history and claim timeline', function () {
    $this->actingAs($this->user);

    DriverPoolLedger::create([
        'employee_id' => $this->driver->id,
        'entry_type' => 'driver_contribution',
        'reference_code' => 'TEST-001',
        'amount' => 300.00,
        'running_balance' => 300.00,
        'description' => 'Test contribution',
    ]);

    $service = app(DriverInsurancePoolService::class);
    $history = $service->getDriverContributionHistory($this->driver);

    expect($history['total_contributed'])->toBe(300.00)
        ->and($history['employee']->id)->toBe($this->driver->id);

    // API endpoint test
    $response = $this->get(route('driver-insurance.driver-history', ['employee' => $this->driver->id]));
    $response->assertOk()
        ->assertJsonPath('employee.code', 'DRV-POOL-01')
        ->assertJsonPath('total_contributed', 300);
});

test('hr can export driver pool periodic financial statement csv', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('driver-insurance.export-statement'));
    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertStreamed();
});

test('ess portal displays driver accident insurance pool widget for driver employees', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->driver->id]));
    $response->assertOk()
        ->assertSee('Driver Accident Insurance Pool')
        ->assertSee('Driver Pool Coverage');
});
