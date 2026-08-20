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

test('phase 2 cleanup: salary bands view displays 6-factor terminology and market benchmark branding', function () {
    $response = $this->get(route('compensation.salary-bands'));
    $response->assertOk();

    // Verify 6-factor calculator branding
    $response->assertSee('6-Factor Candidate Calculator', false);
    $response->assertDontSee('5-Factor Candidate Calculator', false);
    $response->assertDontSee('5-factor weighted candidate', false);

    // Verify updated header & market reference branding
    $response->assertSee('Flexible Compensation &amp; Starting Salary Determination', false);
    $response->assertSee('Market Benchmark Reference Scales', false);
    $response->assertSee('Flexible Pay Scale Active', false);
});
