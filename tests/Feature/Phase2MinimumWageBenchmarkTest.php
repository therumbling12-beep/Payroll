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

test('phase 2: regional minimum wage setting is seeded at 755 and retrievable dynamically', function () {
    CompanySetting::setValue('minimum_wage_daily', 755.00, 'Statutory daily minimum wage rate of the locality');

    $minWage = (float) CompanySetting::getValue('minimum_wage_daily');
    expect($minWage)->toEqual(755.00);

    $response = $this->get(route('compensation.salary-bands'));
    $response->assertOk();
    $response->assertSee('755.00');
    $response->assertSee('19,630.00'); // 755 * 26 days
});

test('phase 2: updating locality minimum wage dynamically reflects on salary bands screen', function () {
    CompanySetting::setValue('minimum_wage_daily', 800.00, 'Updated regional statutory wage');

    $response = $this->get(route('compensation.salary-bands'));
    $response->assertOk();
    $response->assertSee('800.00');
    $response->assertSee('20,800.00'); // 800 * 26 days
});
