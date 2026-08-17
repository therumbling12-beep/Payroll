<?php

declare(strict_types=1);

use App\Models\AccreditedFacility;
use App\Models\AnnualPhysicalExam;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\GroupLifePolicy;
use App\Models\HmoDependent;
use App\Models\HmoEnrollment;
use App\Models\HmoUtilizationLog;
use App\Models\PayrollAuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->dept = Department::create(['name' => 'Logistics & Fleet Operations']);

    $this->employee = Employee::create([
        'employee_code' => 'EMP-801',
        'first_name' => 'Bernardo',
        'last_name' => 'Flores',
        'email' => 'bernardo.flores@example.com',
        'department_id' => $this->dept->id,
        'position' => 'Senior Logistics Officer',
        'employment_status' => 'regular',
        'monthly_rate' => 32000.00,
        'daily_rate' => 1230.77,
    ]);

    CompanySetting::setValue('hmo_provider_name', 'Maxicare Corporate Health');
    CompanySetting::setValue('hmo_base_employee_premium', 1500.00);

    $this->hospital = AccreditedFacility::create([
        'name' => "St. Luke's Medical Center - Global City",
        'facility_type' => 'Tertiary Hospital',
        'region' => 'NCR',
        'address' => '32nd St, BGC, Taguig',
        'contact_number' => '(02) 8789-7700',
        'is_active' => true,
        'is_emergency_ready' => true,
    ]);
});

test('Employee can apply for HMO and register dependents with supporting certificates', function () {
    $idPhoto = UploadedFile::fake()->image('employee_id.jpg');
    $marriageCert = UploadedFile::fake()->create('marriage_cert.pdf', 300);
    $childBirthCert = UploadedFile::fake()->create('child_birth_cert.pdf', 250);

    $payload = [
        'employee_id' => $this->employee->id,
        'coverage_tier' => 'Platinum',
        'hmo_provider' => 'Maxicare Corporate Health',
        'id_photo' => $idPhoto,
        'marriage_cert' => $marriageCert,
        'dependents' => [
            [
                'full_name' => 'Carmela Flores',
                'relationship' => 'Spouse',
                'birth_date' => '1992-05-14',
                'gender' => 'Female',
            ],
            [
                'full_name' => 'Leo Flores',
                'relationship' => 'Child',
                'birth_date' => '2019-11-20',
                'gender' => 'Male',
                'birth_cert' => $childBirthCert,
            ],
        ],
        'notes' => 'New regular employee HMO enrollment',
    ];

    $response = $this->post(route('ess.hmo.apply'), $payload);
    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $enrollment = HmoEnrollment::where('employee_id', $this->employee->id)->first();
    expect($enrollment)->not->toBeNull()
        ->and($enrollment->coverage_tier)->toBe('Platinum')
        ->and($enrollment->dependent_count)->toBe(2)
        ->and($enrollment->enrollment_status)->toBe('submitted');

    expect(HmoDependent::where('hmo_enrollment_id', $enrollment->id)->count())->toBe(2);

    $spouse = HmoDependent::where('hmo_enrollment_id', $enrollment->id)->where('relationship', 'Spouse')->first();
    expect($spouse)->not->toBeNull()->and($spouse->full_name)->toBe('Carmela Flores');
});

test('Employee can request an Emergency Hospital Letter of Authorization (LOA) via ESS', function () {
    $docOrder = UploadedFile::fake()->image('er_order.jpg');

    $payload = [
        'employee_id' => $this->employee->id,
        'patient_type' => 'employee',
        'hospital_name' => "St. Luke's Medical Center - Global City",
        'attending_physician' => 'Dr. Roberto Gomez, MD',
        'diagnosis' => 'Acute gastroenteritis requiring IV hydration and inpatient admission',
        'estimated_amount' => 35000.00,
        'doctor_order_file' => $docOrder,
    ];

    $response = $this->post(route('ess.loa.request'), $payload);
    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $loaLog = HmoUtilizationLog::where('employee_id', $this->employee->id)->latest()->first();
    expect($loaLog)->not->toBeNull()
        ->and($loaLog->service_provider)->toBe("St. Luke's Medical Center - Global City")
        ->and((float) $loaLog->utilized_amount)->toBe(35000.00)
        ->and($loaLog->benefit_type)->toContain('Emergency Hospitalization');

    $audit = PayrollAuditTrail::where('action', 'ESS_HMO_LOA_REQUESTED')
        ->where('model_id', $loaLog->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->user_name)->toBe('Bernardo Flores');
});

test('Employee can schedule an Annual Physical Exam (APE) appointment', function () {
    $payload = [
        'employee_id' => $this->employee->id,
        'schedule_date' => now()->addDays(5)->toDateString(),
        'facility_name' => 'Hi-Precision Diagnostics Plus - Megamall',
        'time_slot' => '07:00 AM - 09:00 AM',
        'notes' => 'Fasting occupational standard package',
    ];

    $response = $this->post(route('ess.ape.schedule'), $payload);
    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $ape = AnnualPhysicalExam::where('employee_id', $this->employee->id)->where('exam_year', (int) date('Y'))->first();
    expect($ape)->not->toBeNull()
        ->and($ape->facility_name)->toBe('Hi-Precision Diagnostics Plus - Megamall')
        ->and($ape->time_slot)->toBe('07:00 AM - 09:00 AM')
        ->and($ape->attendance_status)->toBe('scheduled');

    $audit = PayrollAuditTrail::where('action', 'ESS_APE_APPOINTMENT_SCHEDULED')
        ->where('model_id', $ape->id)
        ->first();

    expect($audit)->not->toBeNull();
});

test('Employee can update Group Life Insurance beneficiaries via ESS', function () {
    $payload = [
        'employee_id' => $this->employee->id,
        'beneficiary_primary_name' => 'Carmela Flores',
        'beneficiary_primary_relation' => 'Spouse',
        'beneficiary_secondary_name' => 'Leo Flores',
        'beneficiary_secondary_relation' => 'Child',
    ];

    $response = $this->post(route('ess.life.beneficiaries'), $payload);
    $response->assertRedirect(route('ess.dashboard', ['employee_id' => $this->employee->id]));

    $policy = GroupLifePolicy::where('employee_id', $this->employee->id)->where('status', 'active')->first();
    expect($policy)->not->toBeNull()
        ->and($policy->beneficiary_primary_name)->toBe('Carmela Flores')
        ->and($policy->beneficiary_primary_relation)->toBe('Spouse')
        ->and($policy->beneficiary_secondary_name)->toBe('Leo Flores')
        ->and($policy->beneficiary_secondary_relation)->toBe('Child');

    $audit = PayrollAuditTrail::where('action', 'ESS_LIFE_BENEFICIARIES_UPDATED')
        ->where('model_id', $policy->id)
        ->first();

    expect($audit)->not->toBeNull();
});
