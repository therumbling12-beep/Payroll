<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\Benefits\MealAllowanceService;
use App\Services\Benefits\ServiceIncentiveLeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('benefits global settings update saves to canonical keys read by service engines', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('benefits.settings.update'), [
        'sil_annual_days' => 7,
        'meal_allowance_daily' => 85.00,
        'christmas_bonus_amount' => 5000.00,
        'christmas_bonus_min_months' => 3,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect((int) CompanySetting::getValue('sil_annual_days'))->toEqual(7)
        ->and((float) CompanySetting::getValue('meal_allowance_daily'))->toEqual(85.00)
        ->and((float) CompanySetting::getValue('christmas_bonus_amount'))->toEqual(5000.00)
        ->and((int) CompanySetting::getValue('christmas_bonus_min_months'))->toEqual(3);

    $dept = Department::create(['name' => 'Logistics', 'code' => 'LOG']);
    $emp = Employee::create([
        'first_name' => 'Pedro',
        'last_name' => 'Penduko',
        'email' => 'pedro.penduko@example.com',
        'employee_code' => 'EMP-TEST-004',
        'department_id' => $dept->id,
        'position' => 'Fleet Driver',
        'hire_date' => now()->subYears(2)->format('Y-m-d'),
        'employment_status' => 'regular',
        'daily_rate' => 755.00,
    ]);

    $silService = app(ServiceIncentiveLeaveService::class);
    $silRecord = $silService->getOrCreateAnnualRecord($emp, (int) now()->year);

    expect((int) $silRecord->entitled_days)->toEqual(7);
});
