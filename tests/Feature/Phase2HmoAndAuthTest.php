<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoGradeLimit;
use App\Models\User;
use App\Services\Benefits\HmoEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('hmo export plans matrix route streams valid csv file', function () {
    HmoGradeLimit::create([
        'grade_min' => 1,
        'grade_max' => 2,
        'title' => 'Basic Healthcare',
        'mbl_amount' => 100000.00,
        'room_and_board' => 'semi_private',
        'max_dependents' => 0,
        'is_active' => true,
    ]);

    $response = $this->get(route('hmo.export-plans'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('hmo enrollment application dynamically assigns tier based on employee salary bracket', function () {
    $department = Department::create(['name' => 'Executive Office', 'code' => 'EXEC']);
    
    // Executive employee with 95,000 monthly rate
    $executive = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'EXEC-001',
        'first_name' => 'Victoria',
        'last_name' => 'President',
        'email' => 'victoria@tripwise.test',
        'position' => 'Chief Operations Officer',
        'monthly_rate' => 95000.00,
        'employment_status' => 'regular',
    ]);

    // Standard employee with 22,000 monthly rate
    $staff = Employee::create([
        'department_id' => $department->id,
        'employee_code' => 'STAFF-001',
        'first_name' => 'Samuel',
        'last_name' => 'Junior',
        'email' => 'samuel@tripwise.test',
        'position' => 'Junior Clerk',
        'monthly_rate' => 22000.00,
        'employment_status' => 'regular',
    ]);

    $service = app(HmoEnrollmentService::class);

    $execApp = $service->submitApplication($executive->id, []);
    expect($execApp->coverage_tier)->toBe('Executive VIP');

    $staffApp = $service->submitApplication($staff->id, []);
    expect($staffApp->coverage_tier)->toBe('Basic Healthcare');
});

test('user can log in successfully with valid credentials and session is regenerated', function () {
    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin@tripwise.test',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->post(route('login.post'), [
        'email' => 'admin@tripwise.test',
        'password' => 'secret123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('user login fails with invalid credentials and redirects back with error', function () {
    User::create([
        'name' => 'Admin User',
        'email' => 'admin@tripwise.test',
        'password' => Hash::make('correct_password'),
    ]);

    $response = $this->from(route('login'))->post(route('login.post'), [
        'email' => 'admin@tripwise.test',
        'password' => 'wrong_password',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('user can log out successfully, session is invalidated and redirected to login', function () {
    $user = User::create([
        'name' => 'HR Manager',
        'email' => 'hrmanager@tripwise.test',
        'password' => Hash::make('password123'),
    ]);

    $this->actingAs($user);
    $this->assertAuthenticatedAs($user);

    $response = $this->post(route('logout'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
