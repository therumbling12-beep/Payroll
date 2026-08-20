<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceBonus;
use App\Models\SalaryComputation;
use App\Models\User;
use App\Services\Compensation\CompensationApprovalService;
use App\Services\GroqAiComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard route requires authentication', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));

    $user = User::factory()->create();
    $authResponse = $this->actingAs($user)->get(route('dashboard'));
    $authResponse->assertOk();
});

test('compensation approval service syncs performance bonus into active weekly cutoff', function () {
    $dept = Department::create(['name' => 'Logistics', 'code' => 'LOG']);
    $emp = Employee::create([
        'first_name' => 'Ramon',
        'last_name' => 'Bautista',
        'email' => 'ramon.bautista@example.com',
        'employee_code' => 'EMP-TEST-BONUS',
        'department_id' => $dept->id,
        'position' => 'Senior Driver',
        'monthly_rate' => 24000.00,
        'daily_rate' => 923.08,
    ]);

    SalaryComputation::create([
        'employee_id' => $emp->id,
        'cutoff_period' => '2026-08-13_19',
        'base_pay' => 5538.46,
        'gross_pay' => 5538.46,
        'total_deductions' => 500.00,
        'net_pay' => 5038.46,
        'status' => 'pending_approval',
    ]);

    $adjustment = \App\Models\CompensationAdjustment::create([
        'employee_id' => $emp->id,
        'type' => 'merit_increase',
        'old_rate' => 24000.00,
        'new_rate' => 26000.00,
        'bonus_amount' => 3000.00,
        'reason' => 'Annual merit review',
        'status' => 'approved',
        'effective_date' => now()->format('Y-m-d'),
    ]);

    $service = app(CompensationApprovalService::class);
    $service->finalizeAndSyncToPayroll($adjustment);

    $bonus = PerformanceBonus::where('employee_id', $emp->id)->latest()->first();

    expect($bonus)->not->toBeNull()
        ->and($bonus->cutoff_period)->toBe('2026-08-13_19')
        ->and((float) $bonus->bonus_amount)->toEqual(3000.00);
});

test('groq ai compliance service reads dynamic minimum wage and deduction ceiling from database settings', function () {
    CompanySetting::setValue('statutory_minimum_wage_daily_rate', 800.00, 'Custom Minimum Wage Floor');
    CompanySetting::setValue('dole_max_deduction_percentage', 0.40, 'Custom 40% Deduction Ceiling');

    $dept = Department::create(['name' => 'HR', 'code' => 'HR']);
    $emp = Employee::create([
        'first_name' => 'Ana',
        'last_name' => 'Lim',
        'email' => 'ana.lim@example.com',
        'employee_code' => 'EMP-TEST-AI',
        'department_id' => $dept->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 25000.00,
    ]);

    $comp = SalaryComputation::create([
        'employee_id' => $emp->id,
        'cutoff_period' => '2026-08-13_19',
        'base_pay' => 5769.23,
        'gross_pay' => 5769.23,
        'total_deductions' => 2600.00, // 45% of gross (exceeds 40% custom ceiling)
        'net_pay' => 3169.23,
        'sss_deduction' => 288.46,
        'philhealth_deduction' => 144.23,
        'pagibig_deduction' => 46.15,
        'status' => 'pending_approval',
    ]);

    $service = app(GroqAiComplianceService::class);
    $log = $service->analyzeCompliance($comp);

    expect($log->status)->toBe('WARNING')
        ->and(collect($log->flagged_issues)->some(fn ($msg) => str_contains($msg, 'ceiling')))->toBeTrue();
});
