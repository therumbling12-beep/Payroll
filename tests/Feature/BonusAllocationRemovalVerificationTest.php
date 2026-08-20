<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sidebar does not contain a Bonus Allocation link (Phase 1)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('compensation.salary-bands'))
        ->assertDontSee('Bonus Allocation');
});

test('bonus allocation GET route returns 404 after route removal (Phase 2)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/compensation/bonus-allocation')
        ->assertNotFound();
});

test('bonus allocation store POST route returns 404 after route removal (Phase 2)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/compensation/bonus-allocation/store', [])
        ->assertNotFound();
});

test('bonus pool calculator API route returns 404 after route removal (Phase 2)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/compensation/api/bonus-pool-calculator', [])
        ->assertNotFound();
});

test('bonus allocation blade view file does not exist on disk after removal (Phase 3)', function () {
    $viewPath = resource_path('views/payroll-benefits/compensation/bonus-allocation.blade.php');

    expect(file_exists($viewPath))->toBeFalse();
});

test('CompensationController no longer has bonusPoolDistributionService property after removal (Phase 4)', function () {
    $controller = app(\App\Http\Controllers\CompensationController::class);

    expect(property_exists($controller, 'bonusPoolDistributionService'))->toBeFalse();
});

test('BonusPoolDistributionService class file does not exist on disk after removal (Phase 5)', function () {
    $servicePath = app_path('Services/Compensation/BonusPoolDistributionService.php');

    expect(file_exists($servicePath))->toBeFalse();
});

test('BonusPoolDistributionService class does not exist in runtime after removal (Phase 5)', function () {
    expect(class_exists(\App\Services\Compensation\BonusPoolDistributionService::class))->toBeFalse();
});
