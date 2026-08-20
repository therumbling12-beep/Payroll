<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\SalaryGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CompanySetting::setValue('minimum_wage_daily', 755.00);
    SalaryGrade::firstOrCreate(
        ['grade_code' => 'PG-1'],
        [
            'position_name' => 'Fleet Driver',
            'job_level' => 'Entry Level',
            'min_salary' => 19630.00,
            'max_salary' => 28000.00,
            'annual_growth_rate' => 5.0,
        ]
    );
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('phase 1 cleanup: salary bands view removes bulk adjustment and calibration log tab', function () {
    $response = $this->get(route('compensation.salary-bands'));
    $response->assertOk();

    // Verify removed obsolete items are NOT present in the DOM
    $response->assertDontSee('Bulk Annual Band Inflation Adjustment', false);
    $response->assertDontSee('Salary Band Calibration Audit Log', false);
    $response->assertDontSee('Bulk Annual Adjustment (%)', false);
    $response->assertDontSee('Calibration History Log', false);

    // Verify essential active components remain intact
    $response->assertSee('Market Benchmark Reference Scales', false);
    $response->assertSee('Personnel Salary Distribution Across Bands', false);
    $response->assertSee('Direct Merit', false);
});
