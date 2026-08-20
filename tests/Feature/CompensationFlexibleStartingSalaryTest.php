<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\SalaryGrade;
use App\Models\User;
use App\Services\Compensation\SalaryDeterminationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CompanySetting::setValue('minimum_wage_daily', 755.00, 'Statutory daily minimum wage');
    $this->grade = SalaryGrade::firstOrCreate(
        ['grade_code' => 'PG-1'],
        [
            'position_name' => 'Driver',
            'job_level' => 'Entry Level',
            'min_salary' => 19630.00,
            'max_salary' => 28000.00,
            'annual_growth_rate' => 5.0,
        ]
    );
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('phase 1: salary determination service computes 6-factor candidate starting salary anchored on 755 minimum wage', function () {
    $service = app(SalaryDeterminationService::class);

    $factors = [
        'experience' => 4,
        'skills' => 5,
        'education' => 3,
        'certifications' => 4,
        'previous_salary' => 3,
        'interview_performance' => 5,
    ];

    $result = $service->calculateRecommendedSalary($this->grade, $factors);

    expect($result['recommended_salary'])->toBeGreaterThanOrEqual(19630.00);
    expect($result['recommended_daily_rate'])->toBeGreaterThanOrEqual(755.00);
    expect($result['minimum_wage_guard']['is_compliant'])->toBeTrue();
    expect($result['factors'])->toHaveKeys(['experience', 'skills', 'education', 'certifications', 'previous_salary', 'interview_performance']);
});

test('phase 1: compensation controller handles 6-factor salary determination request successfully', function () {
    $response = $this->postJson(route('compensation.salary-determination'), [
        'salary_grade_id' => $this->grade->id,
        'experience' => 4,
        'skills' => 4,
        'education' => 3,
        'certifications' => 3,
        'previous_salary' => 3,
        'interview_performance' => 4,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'recommended_salary',
        'recommended_daily_rate',
        'total_score',
        'factors',
        'minimum_wage_guard',
    ]);
});
