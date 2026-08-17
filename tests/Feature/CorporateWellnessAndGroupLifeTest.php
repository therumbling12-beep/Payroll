<?php

declare(strict_types=1);

use App\Models\AnnualPhysicalExam;
use App\Models\Department;
use App\Models\Employee;
use App\Models\GroupLifePolicy;
use App\Services\Benefits\CorporateWellnessAndLifeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->dept = Department::create(['name' => 'Operations & Fleet']);
    $this->employee1 = Employee::create([
        'employee_code' => 'EMP-WELL-01',
        'first_name' => 'Eduardo',
        'last_name' => 'Mendoza',
        'email' => 'eduardo.mendoza@tripease.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Operations Specialist',
        'monthly_rate' => 35000.00,
        'daily_rate' => 1346.15,
        'employment_status' => 'regular',
        'hire_date' => now()->subYears(2),
    ]);

    $this->employee2 = Employee::create([
        'employee_code' => 'EMP-WELL-02',
        'first_name' => 'Karen',
        'last_name' => 'Navarro',
        'email' => 'karen.navarro@tripease.com',
        'department_id' => $this->dept->id,
        'position' => 'Fleet Support Associate',
        'monthly_rate' => 25000.00,
        'daily_rate' => 961.54,
        'employment_status' => 'regular',
        'hire_date' => now()->subYear(),
    ]);
});

test('Step 1: HR schedules individual Annual Physical Exam (APE)', function () {
    $response = $this->post(route('hmo.ape.schedule'), [
        'employee_id' => $this->employee1->id,
        'exam_year' => 2026,
        'schedule_date' => '2026-09-15',
        'time_slot' => '08:00 AM - 10:00 AM',
        'facility_name' => "St. Luke's Medical Center - BGC",
        'package_type' => 'Standard Occupational',
        'notes' => 'Priority occupational health clearance',
    ]);

    $response->assertRedirect(route('hmo.corporate-wellness'))
        ->assertSessionHas('status');

    $exam = AnnualPhysicalExam::where('employee_id', $this->employee1->id)->where('exam_year', 2026)->first();
    expect($exam)->not->toBeNull()
        ->and($exam->schedule_date->format('Y-m-d'))->toBe('2026-09-15')
        ->and($exam->attendance_status)->toBe('scheduled')
        ->and($exam->medical_clearance_status)->toBe('pending_results')
        ->and($exam->facility_name)->toBe("St. Luke's Medical Center - BGC");
});

test('Step 2: HR generates batch APE campaign for department workforce', function () {
    $response = $this->post(route('hmo.ape.batch-schedule'), [
        'exam_year' => 2026,
        'schedule_date' => '2026-10-01',
        'facility_name' => 'Hi-Precision Diagnostics - Makati',
        'package_type' => 'Standard Occupational',
        'department_id' => $this->dept->id,
    ]);

    $response->assertRedirect(route('hmo.corporate-wellness'))
        ->assertSessionHas('status');

    $count = AnnualPhysicalExam::where('exam_year', 2026)->count();
    expect($count)->toBe(2);
});

test('Step 3: HR records APE results, medical clearance and uploads certificate', function () {
    $service = app(CorporateWellnessAndLifeService::class);
    $exam = $service->scheduleApe($this->employee1->id, [
        'exam_year' => 2026,
        'schedule_date' => '2026-09-15',
    ]);

    $cert = UploadedFile::fake()->create('medical_clearance.pdf', 200);

    $response = $this->post(route('hmo.ape.record-results', $exam), [
        'attendance_status' => 'attended',
        'medical_clearance_status' => 'fit_to_work',
        'findings_summary' => 'CBC, Urinalysis, Chest X-Ray Normal. 20/20 Vision.',
        'medical_certificate' => $cert,
    ]);

    $response->assertRedirect(route('hmo.corporate-wellness'))
        ->assertSessionHas('status');

    $exam->refresh();
    expect($exam->attendance_status)->toBe('attended')
        ->and($exam->medical_clearance_status)->toBe('fit_to_work')
        ->and($exam->medical_certificate_path)->not->toBeNull()
        ->and($exam->completed_at)->not->toBeNull();
});

test('Step 4: HR enrolls employee into Corporate Group Life Policy', function () {
    $response = $this->post(route('hmo.group-life.enroll'), [
        'employee_id' => $this->employee1->id,
        'provider_name' => 'Sun Life Grepa Financial',
        'coverage_type' => 'Group Term Life',
        'sum_assured' => 1000000.00,
        'monthly_premium' => 450.00,
        'company_shoulder_pct' => 100.00,
        'beneficiary_primary_name' => 'Rosalinda Mendoza',
        'beneficiary_primary_relation' => 'Spouse',
        'beneficiary_secondary_name' => 'Leo Mendoza',
        'beneficiary_secondary_relation' => 'Son',
        'policy_start_date' => '2026-01-01',
        'policy_end_date' => '2027-01-01',
    ]);

    $response->assertRedirect(route('hmo.corporate-wellness'))
        ->assertSessionHas('status');

    $policy = GroupLifePolicy::where('employee_id', $this->employee1->id)->first();
    expect($policy)->not->toBeNull()
        ->and($policy->coverage_type)->toBe('Group Term Life')
        ->and((float) $policy->sum_assured)->toBe(1000000.00)
        ->and($policy->beneficiary_primary_name)->toBe('Rosalinda Mendoza')
        ->and($policy->status)->toBe('active');
});

test('Step 5: HR updates Group Life policy beneficiaries', function () {
    $service = app(CorporateWellnessAndLifeService::class);
    $policy = $service->enrollGroupLife($this->employee1->id, [
        'beneficiary_primary_name' => 'Rosalinda Mendoza',
        'beneficiary_primary_relation' => 'Spouse',
        'sum_assured' => 500000.00,
    ]);

    $response = $this->post(route('hmo.group-life.update', $policy), [
        'beneficiary_primary_name' => 'Rosalinda Mendoza',
        'beneficiary_primary_relation' => 'Spouse',
        'beneficiary_secondary_name' => 'Maria Mendoza',
        'beneficiary_secondary_relation' => 'Daughter',
        'sum_assured' => 750000.00,
    ]);

    $response->assertRedirect(route('hmo.corporate-wellness'))
        ->assertSessionHas('status');

    $policy->refresh();
    expect((float) $policy->sum_assured)->toBe(750000.00)
        ->and($policy->beneficiary_secondary_name)->toBe('Maria Mendoza');
});

test('Step 6: Corporate Wellness page renders with statutory remittance compliance schedule', function () {
    $response = $this->get(route('hmo.corporate-wellness'));

    $response->assertOk()
        ->assertSee('Annual Physical Exam (APE)')
        ->assertSee('Statutory Remittance Calendar')
        ->assertSee('SSS (Social Security System)')
        ->assertSee('PhilHealth (Philippine Health Insurance)')
        ->assertSee('Pag-IBIG Fund (HDMF)')
        ->assertSee('BIR (Bureau of Internal Revenue)');
});

test('Step 7: ESS Dashboard displays employee APE appointment and Group Life coverage', function () {
    $service = app(CorporateWellnessAndLifeService::class);
    $service->scheduleApe($this->employee1->id, [
        'exam_year' => (int) date('Y'),
        'schedule_date' => '2026-10-15',
        'facility_name' => "St. Luke's Medical Center",
    ]);

    $service->enrollGroupLife($this->employee1->id, [
        'sum_assured' => 500000.00,
        'beneficiary_primary_name' => 'Rosalinda Mendoza',
        'beneficiary_primary_relation' => 'Spouse',
    ]);

    $response = $this->get(route('ess.dashboard', ['employee_id' => $this->employee1->id]));

    $response->assertOk()
        ->assertSee('Annual Physical Exam (APE)')
        ->assertSee("St. Luke's Medical Center")
        ->assertSee('Group Life')
        ->assertSee('PHP 500,000.00');
});
