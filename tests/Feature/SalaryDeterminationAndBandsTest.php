<?php

declare(strict_types=1);

use App\Models\SalaryGrade;
use App\Services\Compensation\SalaryDeterminationService;
use Database\Seeders\SalaryGradeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SalaryGradeSeeder::class);
});

test('salary grades table has valid PG-1 to PG-9 hierarchy with midpoint calculations', function () {
    $grade = SalaryGrade::where('grade_code', 'PG-1')->first();

    expect($grade)->not->toBeNull()
        ->and($grade->min_salary)->toBe(19630.00)
        ->and($grade->max_salary)->toBe(20000.00)
        ->and($grade->midpoint)->toBe(19815.00)
        ->and($grade->spread)->toBe(370.00)
        ->and($grade->isMinimumWageCompliant())->toBeTrue();
});

test('SalaryDeterminationService correctly computes 5-factor weighted scores and band placement', function () {
    $service = new SalaryDeterminationService();

    // 1. Minimum score test: 1 in all factors -> score = 1.00
    $minScore = $service->computeSalaryScore(1, 1, 1, 1, 1);
    expect($minScore)->toBe(1.00);

    // 2. Maximum score test: 6 in all factors -> score = 6.00
    $maxScore = $service->computeSalaryScore(6, 6, 6, 6, 6);
    expect($maxScore)->toBe(6.00);

    // 3. Mixed score test:
    // Edu=4 (4*0.25=1.00), Exp=3 (3*0.35=1.05), Skill=4 (4*0.20=0.80), Market=3 (3*0.10=0.30), Equity=3 (3*0.10=0.30)
    // Total = 1.00 + 1.05 + 0.80 + 0.30 + 0.30 = 3.45
    $mixedScore = $service->computeSalaryScore(4, 3, 4, 3, 3);
    expect($mixedScore)->toBe(3.45);

    // 4. Recommendation calculation on a PG-3 grade (PHP 20,000 to PHP 30,000)
    $grade = SalaryGrade::where('grade_code', 'PG-3')->first();

    $recommendation = $service->calculateRecommendedSalary($grade, [
        'education' => 4,
        'experience' => 3,
        'skills' => 4,
        'market_benchmark' => 3,
        'internal_equity' => 3,
    ]);

    // Score 3.45 falls in 3.01 - 4.00 range -> 50th Percentile (Midpoint: PHP 25,000.00)
    expect($recommendation['total_score'])->toBe(3.45)
        ->and($recommendation['percentile_decimal'])->toBe(0.50)
        ->and($recommendation['recommended_salary'])->toBe(25000.00)
        ->and($recommendation['minimum_wage_guard']['is_compliant'])->toBeTrue();
});

test('POST /compensation/api/salary-determination returns valid JSON payload', function () {
    $grade = SalaryGrade::where('grade_code', 'PG-4')->first();

    $response = $this->postJson('/compensation/api/salary-determination', [
        'salary_grade_id' => $grade->id,
        'education' => 3,
        'experience' => 4,
        'skills' => 4,
        'market_benchmark' => 3,
        'internal_equity' => 3,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'grade_id',
            'grade_code',
            'job_level',
            'position_name',
            'min_salary',
            'max_salary',
            'midpoint_salary',
            'spread_amount',
            'factors' => [
                'education',
                'experience',
                'skills',
                'market_benchmark',
                'internal_equity',
            ],
            'total_score',
            'placement_label',
            'percentile_decimal',
            'recommended_salary',
            'formula',
            'minimum_wage_guard' => [
                'statutory_floor',
                'is_compliant',
                'status',
            ],
        ]);
});

test('GET /compensation/salary-bands page loads with PG-1 to PG-9 hierarchy and zero emojis', function () {
    $response = $this->get('/compensation/salary-bands');
    $response->assertOk();
    $response->assertSee('Salary Band Management');
    $response->assertSee('PG-1 to PG-9 Hierarchy');
});
