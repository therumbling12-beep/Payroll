<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Models\User;
use Database\Seeders\SalaryGradeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SalaryGradeSeeder::class);
    $this->user = User::factory()->create();
    $this->dept = Department::create(['name' => 'Operations']);

    // Create 10 employees to simulate real-world multi-employee roster
    for ($i = 1; $i <= 10; $i++) {
        Employee::create([
            'department_id' => $this->dept->id,
            'employee_code' => "EMP-PERF-{$i}",
            'first_name' => "Worker {$i}",
            'last_name' => 'Test',
            'email' => "worker{$i}@test.com",
            'position' => 'Fleet Driver',
            'employment_status' => 'regular',
            'performance_rating' => 'Satisfactory',
            'years_of_service' => 1.5,
            'current_step' => 1,
            'monthly_rate' => 22000.00,
        ]);
    }
});

test('CompanySetting caches values in runtime memory avoiding redundant database queries', function () {
    CompanySetting::clearRuntimeCache();
    CompanySetting::setValue('test_performance_key', '12345');

    DB::enableQueryLog();

    $val1 = CompanySetting::getValue('test_performance_key');
    $val2 = CompanySetting::getValue('test_performance_key');
    $val3 = CompanySetting::getValue('test_performance_key');

    expect($val1)->toBe('12345')
        ->and($val2)->toBe('12345')
        ->and($val3)->toBe('12345');

    // After initial lookup, subsequent calls should not query the database
    $queries = array_filter(DB::getQueryLog(), fn ($q) => str_contains($q['query'], 'company_settings'));
    expect(count($queries))->toBeLessThanOrEqual(1);
});

test('merit promotions page renders quickly with bounded database queries across multi-employee roster', function () {
    CompanySetting::clearRuntimeCache();
    \App\Services\Compensation\TenureProgressionService::clearGradesCache();
    DB::enableQueryLog();

    $startTime = microtime(true);

    $response = $this->actingAs($this->user)
        ->get(route('compensation.merit-promotions'));

    $duration = microtime(true) - $startTime;

    $response->assertOk();

    // Query count must be strictly bounded and not scale per employee (N+1 eliminated)
    $totalQueries = count(DB::getQueryLog());
    expect($totalQueries)->toBeLessThan(25)
        ->and($duration)->toBeLessThan(2.0); // Page loads in < 2 seconds
});
