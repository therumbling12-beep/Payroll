<?php

use App\Services\CompensationService;
use App\Services\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('compensation service correctly computes automated credential-based counter offer', function () {
    $financialService = new FinancialService();
    $compensationService = new CompensationService($financialService);

    $result = $compensationService->computeCounterOffer('Operations Manager', 5, 2);

    expect($result)->toHaveKey('computed_counter_offer')
        ->and($result['computed_counter_offer'])->toBeGreaterThan(25000.00)
        ->and($result)->toHaveKey('financial_budget_check')
        ->and($result['financial_budget_check'])->toHaveKey('approved');
});
