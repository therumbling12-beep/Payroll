<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dept = Department::create(['name' => 'Fleet Operations', 'code' => 'OPS']);

    // Regular employee with > 1 year tenure (Entitled to 5 SIL days)
    $this->tenuredEmployee = Employee::create([
        'employee_code' => 'EMP-001',
        'first_name' => 'Danilo',
        'last_name' => 'Reyes',
        'email' => 'danilo.reyes@tripease.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Driver',
        'monthly_rate' => 28000.00,
        'daily_rate' => 1076.92,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    // New employee with < 1 year tenure (0 SIL days)
    $this->newEmployee = Employee::create([
        'employee_code' => 'EMP-002',
        'first_name' => 'Maria',
        'last_name' => 'Clara',
        'email' => 'maria.clara@tripease.com',
        'department_id' => $this->dept->id,
        'position' => 'HR Assistant',
        'monthly_rate' => 22000.00,
        'daily_rate' => 846.15,
        'employment_status' => 'regular',
        'hire_date' => now()->subMonths(4),
    ]);

    $this->user = User::factory()->create([
        'name' => 'HR Manager',
        'email' => 'hr@transport.test',
    ]);

    CompanySetting::setValue('meal_allowance_daily', '60.00');
    CompanySetting::setValue('christmas_bonus_amount', '2000.00');
    CompanySetting::setValue('christmas_bonus_min_months', '6');
    CompanySetting::setValue('sil_annual_days', '5');
});

test('benefits administration sil sub-page renders confirmed benefits with zero HMO references', function () {
    $response = $this->actingAs($this->user)->get(route('benefits.sil'));

    $response->assertStatus(200);
    $response->assertSee('Service Incentive Leave');
    $response->assertSee('Danilo Reyes');
    $response->assertDontSee('HMO Plans & Directory');
    $response->assertDontSee('Annual Physical Exam');
    $response->assertDontSee('Group Life Policy');
});

test('sil calculations accurately determine 5 days for tenured employee and 0 for new hire', function () {
    $response = $this->actingAs($this->user)->get(route('benefits.sil'));

    $response->assertStatus(200);
    $response->assertSee('5 Days');
    $response->assertSee('0 Days (Under 1 Year)');
});

test('hr can update dynamic meal allowance settings on dedicated sub-page', function () {
    $response = $this->actingAs($this->user)->post(route('benefits.meal-allowance.settings'), [
        'meal_allowance_daily' => 75.00,
        'meal_allowance_schedule' => 'Daily per Shift',
        'meal_allowance_eligibility' => 'All Active Drivers',
        'meal_allowance_driver_auto' => 1,
    ]);

    $response->assertRedirect(route('benefits.meal-allowance'))
        ->assertSessionHas('status');

    expect((float) CompanySetting::getValue('meal_allowance_daily', 0))->toBe(75.00);
});

test('hr can record sil leave days taken by an employee', function () {
    $response = $this->actingAs($this->user)->post(route('benefits.sil.record'), [
        'employee_id' => $this->tenuredEmployee->id,
        'days_taken' => 2,
        'leave_date' => now()->toDateString(),
        'notes' => 'Family emergency leave under SIL',
    ]);

    $response->assertRedirect(route('benefits.sil'))
        ->assertSessionHas('status');

    $silMap = json_decode((string) CompanySetting::getValue('sil_usage_registry', '{}'), true);
    expect($silMap)->toHaveKey((string) $this->tenuredEmployee->id)
        ->and($silMap[(string) $this->tenuredEmployee->id]['used_days'])->toBe(2);
});
