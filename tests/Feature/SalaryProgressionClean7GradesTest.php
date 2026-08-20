<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Models\User;
use Database\Seeders\SalaryGradeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('system standardizes strictly to 7 salary grades without duplicates', function () {
    $this->seed(SalaryGradeSeeder::class);

    $grades = SalaryGrade::all();
    expect($grades)->toHaveCount(7);
    expect($grades->pluck('grade_code')->toArray())->toBe(['PG-1', 'PG-2', 'PG-3', 'PG-4', 'PG-5', 'PG-6', 'PG-7']);
});

test('merit promotion desk renders clean read-only promotion track without manual promote button', function () {
    $this->seed(SalaryGradeSeeder::class);
    $dept = Department::firstOrCreate(['name' => 'HR']);
    Employee::create([
        'employee_code' => 'EMP-1001',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@tripease.com',
        'department_id' => $dept->id,
        'position' => 'HR Specialist',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
        'hire_date' => now()->subYears(2),
    ]);

    $response = $this->get(route('compensation.merit-promotions'));

    $response->assertOk();
    $response->assertSee('Standard Grade Track');
    $response->assertDontSee('+ Team 3 Promo');
});
