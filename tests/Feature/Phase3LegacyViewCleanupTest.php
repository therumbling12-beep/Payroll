<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('phase 3: all benefits sub-pages render successfully without broken routes', function () {
    $responseIndex = $this->get(route('benefits.index'));
    $responseIndex->assertRedirect(route('benefits.sil'));

    $responseSil = $this->get(route('benefits.sil'));
    $responseSil->assertOk();

    $responseMeal = $this->get(route('benefits.meal-allowance'));
    $responseMeal->assertOk();

    $responseXmas = $this->get(route('benefits.christmas-bonus'));
    $responseXmas->assertOk();
});

test('phase 3: benefits unified settings update route persists company policy adjustments', function () {
    $response = $this->post(route('benefits.settings.update'), [
        'sil_annual_days' => 7,
        'meal_allowance_daily' => 150.00,
        'meal_allowance_schedule' => 'Weekly every Friday',
        'christmas_bonus_amount' => 5000.00,
        'christmas_bonus_min_months' => 6,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    expect((int) CompanySetting::getValue('sil_annual_days'))->toEqual(7);
    expect((float) CompanySetting::getValue('meal_allowance_daily'))->toEqual(150.00);
    expect((float) CompanySetting::getValue('christmas_bonus_amount'))->toEqual(5000.00);
});
