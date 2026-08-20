<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->dept = Department::create(['name' => 'Operations', 'code' => 'OPS']);
    $this->employee = Employee::create([
        'employee_code' => 'EMP-CLEAN-01',
        'first_name' => 'Ricardo',
        'last_name' => 'Dalisay',
        'email' => 'ricardo.dalisay@tripease.test',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Marshal',
        'monthly_rate' => 30000.00,
        'daily_rate' => 1153.85,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);
});

test('hmo routes are completely removed from application routing table', function () {
    expect(Route::has('hmo.plans'))->toBeFalse()
        ->and(Route::has('hmo.enrollments'))->toBeFalse()
        ->and(Route::has('hmo.corporate-wellness'))->toBeFalse()
        ->and(Route::has('hmo.cost-tracking'))->toBeFalse()
        ->and(Route::has('hmo.budget-requests'))->toBeFalse()
        ->and(Route::has('ess.hmo.apply'))->toBeFalse()
        ->and(Route::has('ess.hmo.card'))->toBeFalse();
});

test('clean routes for benefits administration, driver insurance, and ess remain fully operational', function () {
    expect(Route::has('benefits.index'))->toBeTrue()
        ->and(Route::has('benefits.sil'))->toBeTrue()
        ->and(Route::has('benefits.sil.record'))->toBeTrue()
        ->and(Route::has('benefits.sil.settings'))->toBeTrue()
        ->and(Route::has('benefits.meal-allowance'))->toBeTrue()
        ->and(Route::has('benefits.meal-allowance.settings'))->toBeTrue()
        ->and(Route::has('benefits.meal-allowance.export'))->toBeTrue()
        ->and(Route::has('benefits.christmas-bonus'))->toBeTrue()
        ->and(Route::has('benefits.christmas-bonus.settings'))->toBeTrue()
        ->and(Route::has('benefits.christmas-bonus.export'))->toBeTrue()
        ->and(Route::has('driver-insurance.index'))->toBeTrue()
        ->and(Route::has('driver-insurance.file-claim'))->toBeTrue()
        ->and(Route::has('driver-insurance.export-ledger'))->toBeTrue()
        ->and(Route::has('ess.dashboard'))->toBeTrue()
        ->and(Route::has('ess.claims.submit'))->toBeTrue()
        ->and(Route::has('ess.bank-details'))->toBeTrue();

    $this->get(route('benefits.sil'))->assertOk();
    $this->get(route('benefits.meal-allowance'))->assertOk();
    $this->get(route('benefits.christmas-bonus'))->assertOk();
    $this->get(route('driver-insurance.index'))->assertOk();
    $this->get(route('ess.dashboard', ['employee_id' => $this->employee->id]))->assertOk();
});

test('employee model loads cleanly without any missing hmo class references', function () {
    $emp = Employee::with('department')->find($this->employee->id);
    expect($emp)->not->toBeNull()
        ->and($emp->employee_code)->toBe('EMP-CLEAN-01')
        ->and($emp->department->name)->toBe('Operations');
});
