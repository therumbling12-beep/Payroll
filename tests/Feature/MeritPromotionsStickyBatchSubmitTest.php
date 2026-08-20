<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\SalaryGradeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SalaryGradeSeeder::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('merit promotion desk renders inline submission action button', function () {
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
    $response->assertSee('Submit');
    $response->assertSee('Ready to commit compensation plans into active employee payroll profiles.');
});
