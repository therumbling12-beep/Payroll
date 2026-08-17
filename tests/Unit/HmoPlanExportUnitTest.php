<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoGradeLimit;
use App\Services\Benefits\HmoPlanManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('salary grade dynamic formula accurately categorizes salary levels into tiers', function () {
    $planService = app(HmoPlanManagementService::class);

    // Grade 1 baseline fallback (low wage)
    $grade1Mbl = $planService->getMblForGrade(1);
    expect($grade1Mbl['mbl_amount'])->toBeGreaterThanOrEqual(100000.00);

    // Grade 7 Executive Tier
    $grade7Mbl = $planService->getMblForGrade(7);
    expect($grade7Mbl['mbl_amount'])->toBeGreaterThanOrEqual($grade1Mbl['mbl_amount']);
});

test('exportPlansMatrixCsv generates valid streamed CSV response with proper headers', function () {
    HmoGradeLimit::create([
        'grade_min' => 1,
        'grade_max' => 2,
        'title' => 'Basic Healthcare',
        'mbl_amount' => 120000.00,
        'room_and_board' => 'semi_private',
        'max_dependents' => 0,
        'is_active' => true,
    ]);

    HmoGradeLimit::create([
        'grade_min' => 7,
        'grade_max' => 8,
        'title' => 'Executive VIP',
        'mbl_amount' => 300000.00,
        'room_and_board' => 'suite',
        'max_dependents' => 3,
        'is_active' => true,
    ]);

    $planService = app(HmoPlanManagementService::class);
    $response = $planService->exportPlansMatrixCsv();

    expect($response->headers->get('Content-Type'))->toContain('text/csv')
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->headers->get('Content-Disposition'))->toContain('tripwise_hmo_plans_matrix_');

    // Capture streamed output
    ob_start();
    $response->sendContent();
    $csvContent = ob_get_clean();

    expect($csvContent)->toContain('Salary Grade Range')
        ->and($csvContent)->toContain('MBL Annual Limit (PHP)')
        ->and($csvContent)->toContain('Basic Healthcare')
        ->and($csvContent)->toContain('Executive VIP')
        ->and($csvContent)->toContain('300000.00');
});
