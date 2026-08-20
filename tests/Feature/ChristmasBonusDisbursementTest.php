<?php

declare(strict_types=1);

use App\Models\ChristmasBonusDisbursement;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\Benefits\ChristmasBonusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Administration', 'code' => 'ADM']);
    $this->user = User::factory()->create();

    // Tenured Employee (2 years service - 100% full bonus)
    $this->fullTenuredEmp = Employee::create([
        'employee_code' => 'EMP-XMAS-01',
        'first_name' => 'Eduardo',
        'last_name' => 'Navarro',
        'email' => 'eduardo.n@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Operations Officer',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    // Mid-year hire (4 months service - Pro-rated: 4/12 of bonus)
    $this->midYearEmp = Employee::create([
        'employee_code' => 'EMP-XMAS-02',
        'first_name' => 'Grace',
        'last_name' => 'Villanueva',
        'email' => 'grace.v@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Customer Care Agent',
        'monthly_rate' => 22000.00,
        'daily_rate' => 846.15,
        'employment_status' => 'probationary',
        'hire_date' => now()->subMonths(4),
    ]);

    CompanySetting::setValue('christmas_bonus_amount', '2000.00');
    CompanySetting::setValue('christmas_bonus_min_months', '6');
});

test('christmas bonus service calculates full bonus for tenured and pro-rata for mid-year hires', function () {
    $service = app(ChristmasBonusService::class);
    $year = (int) date('Y');
    $standardBonus = 2000.00;

    $fullBonus = $service->calculateForEmployee($this->fullTenuredEmp, $year, $standardBonus, 6);
    $proratedBonus = $service->calculateForEmployee($this->midYearEmp, $year, $standardBonus, 6);

    expect($fullBonus['calculated_bonus_amount'])->toBe(2000.00)
        ->and($fullBonus['is_prorated'])->toBeFalse()
        ->and($proratedBonus['is_prorated'])->toBeTrue()
        ->and((float) $proratedBonus['calculated_bonus_amount'])->toBeGreaterThan(0)
        ->and((float) $proratedBonus['calculated_bonus_amount'])->toBeLessThan(2000.00);
});

test('hr can batch generate christmas bonus allocations for a calendar year', function () {
    $this->actingAs($this->user);
    $year = (int) date('Y');

    $response = $this->post(route('benefits.christmas-bonus.generate'), [
        'bonus_year' => $year,
        'bonus_amount' => 2000.00,
    ]);

    $response->assertRedirect(route('benefits.christmas-bonus', ['year' => $year]))
        ->assertSessionHas('status');

    expect(ChristmasBonusDisbursement::where('bonus_year', $year)->count())->toBe(2);

    $tenuredRecord = ChristmasBonusDisbursement::where('employee_id', $this->fullTenuredEmp->id)->where('bonus_year', $year)->first();
    expect((float) $tenuredRecord->calculated_bonus_amount)->toBe(2000.00)
        ->and($tenuredRecord->status)->toBe('pending');
});

test('hr can approve and release christmas bonus allocation batch', function () {
    $this->actingAs($this->user);
    $year = (int) date('Y');

    ChristmasBonusDisbursement::create([
        'employee_id' => $this->fullTenuredEmp->id,
        'bonus_year' => $year,
        'base_bonus_amount' => 2000.00,
        'months_tenure' => 24.0,
        'is_prorated' => false,
        'calculated_bonus_amount' => 2000.00,
        'status' => 'pending',
    ]);

    // 1. Approve
    $this->post(route('benefits.christmas-bonus.approve'), ['bonus_year' => $year])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ChristmasBonusDisbursement::where('bonus_year', $year)->first()->status)->toBe('hr_approved');

    // 2. Release
    $this->post(route('benefits.christmas-bonus.release'), ['bonus_year' => $year])
        ->assertRedirect()
        ->assertSessionHas('status');

    $released = ChristmasBonusDisbursement::where('bonus_year', $year)->first();
    expect($released->status)->toBe('released_to_payroll')
        ->and($released->released_at)->not->toBeNull();
});

test('hr can export christmas bonus roster csv with pro-rata indicators', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('benefits.christmas-bonus.export'));
    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertStreamed();
});

test('ess dashboard displays christmas bonus projection card for employee', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->fullTenuredEmp->id]));
    $response->assertOk()
        ->assertSee('Christmas Bonus')
        ->assertSee('Qualified')
        ->assertSee('2,000.00');
});
