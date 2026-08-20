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

    // Tenured Driver (2 years tenure)
    $this->driver = Employee::create([
        'employee_code' => 'DRV-101',
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

    // New Hire (3 months tenure)
    $this->newHire = Employee::create([
        'employee_code' => 'EMP-202',
        'first_name' => 'Maria',
        'last_name' => 'Clara',
        'email' => 'maria.clara@tripease.com',
        'department_id' => $this->dept->id,
        'position' => 'HR Assistant',
        'monthly_rate' => 22000.00,
        'daily_rate' => 846.15,
        'employment_status' => 'probationary',
        'hire_date' => now()->subMonths(3),
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

test('benefits index redirects smoothly to dedicated sil sub-page', function () {
    $response = $this->actingAs($this->user)->get(route('benefits.index'));

    $response->assertRedirect(route('benefits.sil'));
});

test('sil sub page renders accurately with tenure entitlement and leave pool', function () {
    $response = $this->actingAs($this->user)->get(route('benefits.sil'));

    $response->assertStatus(200);
    $response->assertSee('Service Incentive Leave');
    $response->assertSee('Danilo Reyes');
    $response->assertSee('5 Days');
    $response->assertSee('0 Days (Under 1 Year)');
    $response->assertDontSee('HMO Plans');
});

test('hr can record sil leave days taken by an employee', function () {
    $response = $this->actingAs($this->user)->post(route('benefits.sil.record'), [
        'employee_id' => $this->driver->id,
        'days_taken' => 2,
        'leave_date' => now()->toDateString(),
        'notes' => 'Personal rest day',
    ]);

    $response->assertRedirect(route('benefits.sil'))
        ->assertSessionHas('status');

    $silMap = json_decode((string) CompanySetting::getValue('sil_usage_registry', '{}'), true);
    expect($silMap)->toHaveKey((string) $this->driver->id)
        ->and($silMap[(string) $this->driver->id]['used_days'])->toBe(2);
});

test('hr can update sil annual policy setting', function () {
    $response = $this->actingAs($this->user)->post(route('benefits.sil.settings'), [
        'sil_annual_days' => 7,
    ]);

    $response->assertRedirect(route('benefits.sil'))
        ->assertSessionHas('status');

    expect((int) CompanySetting::getValue('sil_annual_days', 0))->toBe(7);
});

test('meal allowance sub page renders driver roster and live subsidy math', function () {
    $response = $this->actingAs($this->user)->get(route('benefits.meal-allowance'));

    $response->assertStatus(200);
    $response->assertSee('Meal Allowance Subsidy');
    $response->assertSee('PHP 60.00');
    $response->assertSee('Danilo Reyes');
    $response->assertSee('De Minimis Tax Exemption');
});

test('hr can update meal allowance daily rate dynamically', function () {
    $response = $this->actingAs($this->user)->post(route('benefits.meal-allowance.settings'), [
        'meal_allowance_daily' => 80.00,
        'meal_allowance_schedule' => 'Daily per Worked Shift',
        'meal_allowance_eligibility' => 'All Active Drivers',
        'meal_allowance_driver_auto' => 1,
    ]);

    $response->assertRedirect(route('benefits.meal-allowance'))
        ->assertSessionHas('status');

    expect((float) CompanySetting::getValue('meal_allowance_daily', 0))->toBe(80.00);
});

test('meal allowance roster can be exported as streamed csv', function () {
    $response = $this->actingAs($this->user)->get(route('benefits.meal-allowance.export'));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('christmas bonus sub page renders qualification roster with tenure filtering', function () {
    $response = $this->actingAs($this->user)->get(route('benefits.christmas-bonus'));

    $response->assertStatus(200);
    $response->assertSee('Christmas Bonus');
    $response->assertSee('PHP 2,000.00');
    $response->assertSee('Danilo Reyes');
    $response->assertSee('Qualified');
    $response->assertSee('Pro-Rated');
});

test('hr can update christmas bonus amount and minimum tenure threshold', function () {
    $response = $this->actingAs($this->user)->post(route('benefits.christmas-bonus.settings'), [
        'christmas_bonus_amount' => 3500.00,
        'christmas_bonus_min_months' => 2,
        'christmas_bonus_enabled' => 1,
    ]);

    $response->assertRedirect(route('benefits.christmas-bonus'))
        ->assertSessionHas('status');

    expect((float) CompanySetting::getValue('christmas_bonus_amount', 0))->toBe(3500.00)
        ->and((int) CompanySetting::getValue('christmas_bonus_min_months', 0))->toBe(2);
});

test('christmas bonus allocation roster can be exported as streamed csv', function () {
    $response = $this->actingAs($this->user)->get(route('benefits.christmas-bonus.export'));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
