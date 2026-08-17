<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EssClaimSubmissionRequest;
use App\Http\Requests\EssEmergencyLoaRequest;
use App\Http\Requests\HmoEnrollmentApplicationRequest;
use App\Models\AccreditedFacility;
use App\Models\AnnualPhysicalExam;
use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\GroupLifePolicy;
use App\Models\HmoEnrollment;
use App\Models\HmoUtilizationLog;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;
use App\Services\Benefits\CorporateWellnessAndLifeService;
use App\Services\Benefits\DriverInsurancePoolService;
use App\Services\Benefits\HmoEnrollmentService;
use App\Services\Benefits\HmoPlanManagementService;
use App\Services\Claims\ClaimTaxabilityService;
use App\Services\Claims\FuelReimbursementValidationService;
use App\Services\Claims\MaternityBenefitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EssController extends Controller
{
    public function __construct(
        protected HmoEnrollmentService $enrollmentService,
        protected HmoPlanManagementService $planService,
        protected FuelReimbursementValidationService $fuelService,
        protected ClaimTaxabilityService $taxabilityService,
        protected MaternityBenefitService $maternityService,
        protected DriverInsurancePoolService $driverPoolService,
        protected CorporateWellnessAndLifeService $wellnessService
    ) {}

    /**
     * Employee Self-Service (ESS) Portal Dashboard
     */
    public function index(Request $request): View
    {
        $employeeId = $request->query('employee_id');
        $employees = Employee::orderBy('first_name')->get();
        
        $selectedEmployee = $employeeId ? Employee::find($employeeId) : $employees->first();

        $hmo = null;
        $digitalCardPayload = null;
        $latestComputation = null;
        $claims = collect();
        $apeExam = null;
        $groupLife = null;
        $hmoConfig = $this->planService->getHmoConfiguration();
        $medicalUtilized = 0.00;
        $medicalCap = (float) CompanySetting::getValue('medical_cash_allowance_annual_cap', 10000.00);
        $maternityCalculations = [];
        $maternityTypes = MaternityBenefitService::MATERNITY_TYPES;

        if ($selectedEmployee) {
            $hmo = HmoEnrollment::with(['dependents', 'utilizationLogs'])
                ->where('employee_id', $selectedEmployee->id)
                ->first();

            if ($hmo) {
                $digitalCardPayload = $this->enrollmentService->generateDigitalCardPayload($hmo);
            }

            $apeExam = AnnualPhysicalExam::where('employee_id', $selectedEmployee->id)->where('exam_year', (int) date('Y'))->first();
            $groupLife = GroupLifePolicy::where('employee_id', $selectedEmployee->id)->where('status', 'active')->first();

            $latestComputation = SalaryComputation::where('employee_id', $selectedEmployee->id)->latest()->first();
            $claims = Claim::with(['categoryModel'])
                ->where('employee_id', $selectedEmployee->id)
                ->latest()
                ->take(20)
                ->get();

            $medicalUtilized = $this->taxabilityService->getEmployeeMedicalUtilizedThisYear($selectedEmployee);

            $maternityCalculations = [
                'normal_caesarean' => $this->maternityService->computeMaternityBenefit($selectedEmployee, 'normal_caesarean'),
                'solo_parent' => $this->maternityService->computeMaternityBenefit($selectedEmployee, 'solo_parent'),
                'miscarriage_emergency' => $this->maternityService->computeMaternityBenefit($selectedEmployee, 'miscarriage_emergency'),
            ];
        }

        $isOpenEnrollmentActive = $this->planService->isOpenEnrollmentActive();
        $openEnrollmentWindow = $this->planService->getOpenEnrollmentWindow();
        $categories = ClaimCategory::where('is_active', true)->orderBy('type')->orderBy('name')->get();
        $availableHmoPlans = $this->planService->getGradeMblMatrix();
        $accreditedFacilities = AccreditedFacility::where('is_active', true)->orderBy('name')->get();
        $fuelSettings = [
            'pump_price' => $this->fuelService->getDefaultPumpPrice(),
            'efficiency' => $this->fuelService->getDefaultEfficiency(),
            'tolerance_pct' => $this->fuelService->getTolerancePercentage(),
        ];

        return view('ess.dashboard', compact(
            'employees',
            'selectedEmployee',
            'hmo',
            'digitalCardPayload',
            'apeExam',
            'groupLife',
            'latestComputation',
            'claims',
            'hmoConfig',
            'availableHmoPlans',
            'accreditedFacilities',
            'isOpenEnrollmentActive',
            'openEnrollmentWindow',
            'categories',
            'fuelSettings',
            'medicalUtilized',
            'medicalCap',
            'maternityCalculations',
            'maternityTypes'
        ));
    }

    /**
     * Employee Self-Service HMO Application Submission (known.md §8.6 Step 1)
     */
    public function applyHmo(HmoEnrollmentApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employeeId = (int) $validated['employee_id'];
        $dependents = $validated['dependents'] ?? [];

        $this->enrollmentService->submitApplication(
            $employeeId,
            $validated,
            $request->file('id_photo'),
            $request->file('marriage_cert'),
            $dependents
        );

        return redirect()->route('ess.dashboard', ['employee_id' => $employeeId])
            ->with('status', 'Your HMO Healthcare Coverage Application has been submitted to HR Team 4 for verification.');
    }

    /**
     * API: Get ESS Digital HMO Card Live Payload (known.md §8.6 Step 5)
     */
    public function digitalCard(Request $request): JsonResponse
    {
        $employeeId = $request->query('employee_id');
        if (! $employeeId) {
            return response()->json(['error' => 'Employee ID is required.'], 422);
        }

        $hmo = HmoEnrollment::where('employee_id', $employeeId)->first();
        if (! $hmo) {
            return response()->json(['error' => 'No HMO coverage record found for this employee.'], 404);
        }

        $payload = $this->enrollmentService->generateDigitalCardPayload($hmo);

        return response()->json($payload);
    }

    /**
     * Employee Bank / Payment Method Details Setup
     */
    public function updateBankDetails(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'payment_method' => 'required|string|in:cash,bank',
            'bank_name' => 'nullable|required_if:payment_method,bank|string|max:255',
            'bank_account_number' => 'nullable|required_if:payment_method,bank|string|max:255',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $employee->update([
            'payment_method' => $validated['payment_method'],
            'bank_name' => $validated['bank_name'],
            'bank_account_number' => $validated['bank_account_number'],
        ]);

        return redirect()->back()->with('status', 'Employee bank deposit & payment details updated successfully!');
    }

    /**
     * Submit an Employee or Driver Self-Service Claim with Receipt
     */
    public function submitClaim(EssClaimSubmissionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employee = Employee::findOrFail($validated['employee_id']);
        $type = (string) $validated['type'];

        // Delegate Statutory Maternity Benefit Advance
        if ($type === 'maternity') {
            $maternityClaim = $this->maternityService->fileMaternityClaim($validated, $request->file('receipt_file'));

            return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
                ->with('status', "Your RA 11210 Statutory Maternity Benefit Advance application [{$maternityClaim->receipt_number}] for PHP " . number_format((float)$maternityClaim->amount, 2) . " has been submitted to HR for validation.");
        }

        // Delegate Driver Road Incident / Accident Relief Claim
        if ($type === 'accident') {
            $accidentData = [
                'employee_id' => $employee->id,
                'incident_type' => $validated['description'] ? 'Road Incident' : 'Work Injury',
                'incident_date' => $validated['incident_date'] ?? $validated['expense_date'],
                'bill_amount' => (float) $validated['amount'],
                'description' => $validated['description'] ?? 'Driver road incident relief claim filed via ESS portal',
                'vehicle_plate_number' => $validated['incident_location'] ?? null,
                'diagnosis' => $validated['hospital_name'] ?? null,
            ];

            $accidentClaim = $this->driverPoolService->fileClaim(
                $employee->id,
                $accidentData,
                $request->file('receipt_file'),
                $request->file('receipt_file'),
                $request->file('receipt_file')
            );

            return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
                ->with('status', "Your Driver Emergency Road Incident Relief claim [{$accidentClaim->incident_number}] for PHP " . number_format((float)$accidentClaim->bill_amount, 2) . " has been submitted to HR Team 4.");
        }

        // Standard Expense or Medical Assistance Reimbursement
        $amount = (float) $validated['amount'];
        $receiptNumber = (string) $validated['receipt_number'];
        $expenseDate = (string) $validated['expense_date'];
        $description = $validated['description'] ?? null;
        $category = null;
        $categoryId = ! empty($validated['category_id']) ? (int) $validated['category_id'] : null;

        $attachmentPath = null;
        if ($request->hasFile('receipt_file')) {
            $attachmentPath = $request->file('receipt_file')->store('attachments', 'public');
        } else {
            $attachmentPath = 'attachments/' . strtolower($type) . '-receipt.pdf';
        }

        $nonTaxable = $amount;
        $taxable = 0.00;
        $autoValidated = false;
        $fuelVariancePct = null;
        $distanceKm = ! empty($validated['distance_traveled_km']) ? (float) $validated['distance_traveled_km'] : null;
        $fuelPrice = ! empty($validated['fuel_pump_price']) ? (float) $validated['fuel_pump_price'] : null;
        $fuelEfficiency = ! empty($validated['vehicle_fuel_efficiency_kpl']) ? (float) $validated['vehicle_fuel_efficiency_kpl'] : null;

        if ($type === 'medical' && ! $categoryId) {
            $medCat = ClaimCategory::firstOrCreate(
                ['code' => 'MED-AID'],
                [
                    'name' => 'Medical & Medicine Assistance',
                    'type' => 'medical',
                    'tax_classification' => 'de_minimis',
                    'is_active' => true,
                ]
            );
            $categoryId = $medCat->id;
        }

        if ($categoryId) {
            $catModel = ClaimCategory::find($categoryId);
            if ($catModel) {
                $category = $catModel->name;
                $taxResult = $this->taxabilityService->classifyClaim($catModel, $amount, $employee);
                $nonTaxable = (float) $taxResult['non_taxable_amount'];
                $taxable = (float) $taxResult['taxable_amount'];
            }
        }

        if ($distanceKm && $distanceKm > 0) {
            $fuelResult = $this->fuelService->validateFuelClaim($amount, $distanceKm, $fuelEfficiency, $fuelPrice);
            $autoValidated = (bool) $fuelResult['is_within_tolerance'];
            $fuelVariancePct = (float) $fuelResult['variance_pct'];
        }

        $claim = Claim::create([
            'employee_id' => $employee->id,
            'category_id' => $categoryId,
            'category' => $category ?: ucfirst($type) . ' Claim',
            'type' => $type,
            'amount' => $amount,
            'non_taxable_amount' => $nonTaxable,
            'taxable_amount' => $taxable,
            'description' => $description ?: "Self-service {$type} claim filed via ESS portal",
            'receipt_number' => $receiptNumber,
            'merchant_name' => $validated['merchant_name'] ?? null,
            'status' => 'pending',
            'approval_status' => 'pending_hr',
            'expense_date' => $expenseDate,
            'attachment_path' => $attachmentPath,
            'distance_traveled_km' => $distanceKm,
            'fuel_pump_price' => $fuelPrice,
            'vehicle_fuel_efficiency_kpl' => $fuelEfficiency,
            'fuel_variance_pct' => $fuelVariancePct,
            'auto_validated' => $autoValidated,
            'effective_date' => now(),
        ]);

        $auditAction = $type === 'medical' ? 'ESS_MEDICAL_CLAIM_SUBMITTED' : 'ESS_CLAIM_SUBMITTED';

        PayrollAuditTrail::create([
            'action' => $auditAction,
            'model_type' => Claim::class,
            'model_id' => $claim->id,
            'user_name' => $employee->first_name . ' ' . $employee->last_name,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'old_values' => [],
            'new_values' => [
                'claim_id' => $claim->id,
                'receipt_number' => $receiptNumber,
                'amount' => $amount,
                'type' => $type,
                'non_taxable_amount' => $nonTaxable,
                'taxable_amount' => $taxable,
                'auto_validated' => $autoValidated,
            ],
        ]);

        return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
            ->with('status', "Your claim [{$receiptNumber}] for PHP " . number_format($amount, 2) . " has been submitted to HR for validation.");
    }

    /**
     * Employee Self-Service Emergency Hospital Letter of Authorization (LOA) Request
     */
    public function requestEmergencyLoa(EssEmergencyLoaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employee = Employee::findOrFail($validated['employee_id']);
        $patientType = $validated['patient_type'];
        $dependentId = ! empty($validated['dependent_id']) ? (int) $validated['dependent_id'] : null;
        $hospitalName = $validated['hospital_name'];
        $physician = $validated['attending_physician'] ?? null;
        $diagnosis = $validated['diagnosis'];
        $estimatedAmount = ! empty($validated['estimated_amount']) ? (float) $validated['estimated_amount'] : 15000.00;

        $docPath = null;
        if ($request->hasFile('doctor_order_file')) {
            $docPath = $request->file('doctor_order_file')->store('uploads/hmo/loa', 'public');
        }

        $loaCode = 'LOA-' . date('Y') . '-' . strtoupper(Str::random(6));
        $hmo = HmoEnrollment::where('employee_id', $employee->id)->first();
        $remainingBalance = $hmo ? $hmo->remainingBalance() : 100000.00;

        $log = HmoUtilizationLog::create([
            'employee_id' => $employee->id,
            'hmo_enrollment_id' => $hmo?->id,
            'benefit_type' => 'Emergency Hospitalization (LOA: ' . $loaCode . ')',
            'service_provider' => $hospitalName,
            'utilized_amount' => $estimatedAmount,
            'remaining_balance' => max(0.00, $remainingBalance - $estimatedAmount),
            'utilized_at' => now()->toDateString(),
            'description' => "Emergency LOA Request ({$loaCode}): " . ($patientType === 'dependent' ? 'Family Dependent' : 'Primary Employee') . " - Diagnosis: {$diagnosis}",
        ]);

        PayrollAuditTrail::create([
            'action' => 'ESS_HMO_LOA_REQUESTED',
            'model_type' => HmoUtilizationLog::class,
            'model_id' => $log->id,
            'user_name' => $employee->first_name . ' ' . $employee->last_name,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'old_values' => [],
            'new_values' => [
                'loa_code' => $loaCode,
                'hospital_name' => $hospitalName,
                'patient_type' => $patientType,
                'estimated_amount' => $estimatedAmount,
            ],
        ]);

        return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
            ->with('status', "Your Emergency Hospital Letter of Authorization (LOA) request [{$loaCode}] for {$hospitalName} has been submitted to HR Team 4.");
    }

    /**
     * Employee Self-Service Annual Physical Exam (APE) Appointment Scheduler
     */
    public function scheduleApe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'schedule_date' => 'required|date|after_or_equal:today',
            'facility_name' => 'required|string|max:150',
            'time_slot' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $ape = $this->wellnessService->scheduleApe($employee->id, $validated);

        PayrollAuditTrail::create([
            'action' => 'ESS_APE_APPOINTMENT_SCHEDULED',
            'model_type' => AnnualPhysicalExam::class,
            'model_id' => $ape->id,
            'user_name' => $employee->first_name . ' ' . $employee->last_name,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'old_values' => [],
            'new_values' => [
                'facility_name' => $validated['facility_name'],
                'schedule_date' => $validated['schedule_date'],
                'time_slot' => $validated['time_slot'] ?? '08:00 AM - 10:00 AM',
            ],
        ]);

        return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
            ->with('status', "Your Annual Physical Exam (APE) appointment at {$validated['facility_name']} has been scheduled for {$validated['schedule_date']}.");
    }

    /**
     * Employee Self-Service Group Life Insurance Beneficiary Setup
     */
    public function updateLifeBeneficiaries(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'beneficiary_primary_name' => 'required|string|max:150',
            'beneficiary_primary_relation' => 'required|string|max:50',
            'beneficiary_secondary_name' => 'nullable|string|max:150',
            'beneficiary_secondary_relation' => 'nullable|string|max:50',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $policy = GroupLifePolicy::where('employee_id', $employee->id)->where('status', 'active')->first();

        if (! $policy) {
            $policy = $this->wellnessService->enrollGroupLife($employee->id, $validated);
        } else {
            $this->wellnessService->updateGroupLifeBeneficiaries($policy, $validated);
        }

        PayrollAuditTrail::create([
            'action' => 'ESS_LIFE_BENEFICIARIES_UPDATED',
            'model_type' => GroupLifePolicy::class,
            'model_id' => $policy->id,
            'user_name' => $employee->first_name . ' ' . $employee->last_name,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'old_values' => [],
            'new_values' => [
                'primary' => $validated['beneficiary_primary_name'] . ' (' . $validated['beneficiary_primary_relation'] . ')',
                'secondary' => $validated['beneficiary_secondary_name'] ?? 'None',
            ],
        ]);

        return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
            ->with('status', 'Your Group Life Insurance policy beneficiaries have been updated successfully.');
    }
}
