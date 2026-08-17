<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AccreditedFacilityRequest;
use App\Http\Requests\ApeResultsRequest;
use App\Http\Requests\ApeScheduleRequest;
use App\Http\Requests\DriverAccidentClaimRequest;
use App\Http\Requests\DriverPoolConfigRequest;
use App\Http\Requests\GroupLifeEnrollmentRequest;
use App\Http\Requests\HmoConfigurationRequest;
use App\Models\AccidentClaim;
use App\Models\AccreditedFacility;
use App\Models\AnnualPhysicalExam;
use App\Models\BenefitType;
use App\Models\BudgetRequisition;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\DriverPoolLedger;
use App\Models\Employee;
use App\Models\GroupLifePolicy;
use App\Models\HmoDependent;
use App\Models\HmoEnrollment;
use App\Models\HmoGradeLimit;
use App\Models\HmoUtilizationLog;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;
use App\Services\Benefits\CorporateWellnessAndLifeService;
use App\Services\Benefits\DriverInsurancePoolService;
use App\Services\Benefits\HmoEnrollmentService;
use App\Services\Benefits\HmoPlanManagementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HmoController extends Controller
{
    public function __construct(
        protected HmoPlanManagementService $planService,
        protected HmoEnrollmentService $enrollmentService,
        protected DriverInsurancePoolService $driverPoolService,
        protected CorporateWellnessAndLifeService $wellnessService
    ) {}

    /**
     * 4.4 HMO Plan Management & Enrollment
     */
    public function plans(Request $request): View
    {
        $gradeLimits = $this->planService->getGradeMblMatrix();
        $hmoConfig = $this->planService->getHmoConfiguration();
        $benefitTypes = BenefitType::orderBy('name')->get();
        $tab = $request->query('tab', 'matrix');

        return view('payroll-benefits.hmo.plans', compact(
            'gradeLimits',
            'hmoConfig',
            'benefitTypes',
            'tab'
        ));
    }

    /**
     * Update HMO Enterprise Configuration (known.md §8.4)
     */
    public function updateHmoConfig(HmoConfigurationRequest $request): RedirectResponse
    {
        $this->planService->updateHmoConfiguration($request->validated());

        return redirect()->route('hmo.plans', ['tab' => 'matrix'])->with('status', 'HMO enterprise policy configuration updated successfully.');
    }

    /**
     * Reset HMO Enterprise Configuration to Factory Defaults
     */
    public function resetHmoConfiguration(): RedirectResponse
    {
        $this->planService->resetHmoConfigurationToDefaults();

        PayrollAuditTrail::create([
            'action' => 'HMO_POLICY_CONFIG_RESET',
            'model_type' => CompanySetting::class,
            'user_name' => auth()->user()?->name ?? 'System Admin',
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'old_values' => [],
            'new_values' => ['status' => 'Reset to company defaults (Maxicare 80/20, PHP 1,800 / PHP 1,200 base premiums)'],
        ]);

        return redirect()->route('hmo.plans', ['tab' => 'matrix'])->with('status', 'HMO provider policies, co-sharing percentages, and base premiums have been reset to company defaults.');
    }

    /**
     * Store new Accredited Medical Facility (known.md §8.11 Item 7)
     */
    public function storeFacility(AccreditedFacilityRequest $request): RedirectResponse
    {
        AccreditedFacility::create($request->validated());

        return redirect()->route('hmo.plans')->with('status', 'Accredited medical facility registered to provider network.');
    }

    /**
     * Export Official Provider Master Roster CSV (known.md §8.11 Item 4)
     */
    public function exportRoster(): StreamedResponse
    {
        return $this->planService->exportProviderRosterCsv();
    }

    /**
     * Export Grade-Based Maximum Benefit Limit (MBL) Plans Matrix CSV
     */
    public function exportPlans(): StreamedResponse
    {
        return $this->planService->exportPlansMatrixCsv();
    }

    /**
     * API: Live Grade-Based MBL & Premium Co-Share Resolver
     */
    public function apiCalculateGradeMbl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'salary_grade' => ['required', 'integer', 'min:1', 'max:20'],
            'total_premium' => ['nullable', 'numeric', 'min:0'],
            'dependent_count' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        $mbl = $this->planService->getMblForGrade((int) $validated['salary_grade']);
        $premium = (float) ($validated['total_premium'] ?? 1800.00);
        $dependents = (int) ($validated['dependent_count'] ?? 0);

        $sharing = $this->planService->calculatePremiumSharing($premium, $dependents);

        return response()->json([
            'grade_info' => $mbl,
            'premium_sharing' => $sharing,
        ]);
    }

    /**
     * 4.6 Employee HMO Enrollments & Workforce Roster (Phase 2 Dedicated Sub-Page)
     */
    public function enrollments(Request $request): View
    {
        $search = $request->query('search');
        $tier = $request->query('tier');
        $status = $request->query('status');
        $filter = $request->query('filter', 'all');
        $tab = $request->query('tab', 'roster');

        $query = HmoEnrollment::with(['employee.department', 'dependents', 'utilizationLogs'])->latest();

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->search($search);
            })->orWhere('hmo_card_number', 'like', "%{$search}%")
              ->orWhere('hmo_provider', 'like', "%{$search}%");
        }

        if ($tier && $tier !== 'all') {
            $query->where('coverage_tier', $tier);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($filter === 'drivers') {
            $query->whereHas('employee', function ($q) {
                $q->where('position', 'like', '%Driver%')
                  ->orWhere('position', 'like', '%driver%');
            });
        } elseif ($filter === 'office') {
            $query->whereHas('employee', function ($q) {
                $q->where('position', 'not like', '%Driver%')
                  ->where('position', 'not like', '%driver%');
            });
        } elseif ($filter === 'expiring') {
            $query->where('status', 'active')
                ->whereNotNull('coverage_end_date')
                ->whereDate('coverage_end_date', '>=', now())
                ->whereDate('coverage_end_date', '<=', now()->addDays(30));
        }

        $enrollments = $query->paginate(10)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();
        $utilizationLogs = HmoUtilizationLog::with('employee')->latest()->take(15)->get();

        // Calculate statistics
        $totalActive = HmoEnrollment::where('status', 'active')->count();
        $totalAnnualPremiums = (float) HmoEnrollment::where('status', 'active')->sum('monthly_premium') * 12;
        $expiringSoonCount = HmoEnrollment::where('status', 'active')
            ->whereNotNull('coverage_end_date')
            ->whereDate('coverage_end_date', '>=', now())
            ->whereDate('coverage_end_date', '<=', now()->addDays(30))
            ->count();

        $stats = [
            'total_active' => $totalActive,
            'total_annual_premiums' => $totalAnnualPremiums,
            'expiring_soon' => $expiringSoonCount,
            'total_utilized' => (float) HmoUtilizationLog::sum('utilized_amount'),
            'pending_applications' => HmoEnrollment::whereIn('enrollment_status', ['submitted', 'hr_approved', 'budget_requested'])->count(),
            'verified_dependents' => HmoDependent::where('status', 'verified')->count(),
        ];

        // Approvals Queue
        $pendingApplications = HmoEnrollment::whereIn('enrollment_status', ['submitted', 'hr_approved', 'budget_requested'])
            ->with(['employee.department', 'dependents'])
            ->latest()
            ->get();

        // Employees with Eligibility Mapping
        $employeeEligibility = $employees->map(function ($emp) {
            return [
                'employee' => $emp,
                'eligibility' => $this->enrollmentService->getEligibilityStatus($emp),
                'active_enrollment' => $emp->hmoEnrollments->where('status', 'active')->first(),
            ];
        });

        $hmoConfig = $this->planService->getHmoConfiguration();

        return view('payroll-benefits.hmo.enrollments', compact(
            'enrollments',
            'employees',
            'utilizationLogs',
            'stats',
            'pendingApplications',
            'employeeEligibility',
            'hmoConfig',
            'search',
            'tier',
            'status',
            'filter',
            'tab'
        ));
    }

    /**
     * 1-Click Sync Employee HMO Premium Shares to Payroll Deductions (known.md §8.6 Step 4 & 7)
     */
    public function syncPayrollDeductions(): RedirectResponse
    {
        $result = $this->enrollmentService->syncHmoDeductionsToPayroll();

        return redirect()->route('hmo.enrollments', ['tab' => 'roster'])
            ->with('status', "Synced {$result['synced_count']} active employee HMO policies with Payroll. Total monthly employee deduction: PHP " . number_format($result['total_employee_deductions'], 2) . '.');
    }

    /**
     * Deactivate Employee HMO Coverage on Separation / Resignation (known.md §8.10)
     */
    public function deactivateEnrollment(HmoEnrollment $enrollment, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'separation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->enrollmentService->deactivateSeparatedEmployeeHmo(
            $enrollment,
            $validated['separation_reason'] ?? 'Employee Resignation / Separation'
        );

        return redirect()->route('hmo.enrollments', ['tab' => 'roster'])
            ->with('status', "HMO coverage for {$enrollment->employee?->first_name} {$enrollment->employee?->last_name} has been terminated.");
    }

    /**
     * Step 2: HR Team 4 Review & Validate Eligibility (known.md §8.6 Step 2)
     */
    public function validateEnrollmentHr(HmoEnrollment $enrollment, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->enrollmentService->validateApplicationByHr($enrollment, $validated['remarks'] ?? null);

        return redirect()->route('hmo.enrollments', ['tab' => 'approvals'])->with('status', 'Employee HMO application verified and approved by HR Team 4.');
    }

    /**
     * Step 3: Request Budget Allocation to Team 5 Finance (known.md §8.6 Step 3)
     */
    public function requestEnrollmentBudget(HmoEnrollment $enrollment): RedirectResponse
    {
        $budgetReq = $this->enrollmentService->requestBudgetForEnrollment($enrollment);

        return redirect()->route('hmo.enrollments', ['tab' => 'approvals'])->with('status', "Budget allocation request #{$budgetReq->id} submitted to Finance Budget Officer.");
    }

    /**
     * Step 4: Finalize Provider Enrollment & Issue Official Member Card (known.md §8.6 Step 4)
     */
    public function activateEnrollment(HmoEnrollment $enrollment, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hmo_card_number' => ['required', 'string', 'max:100'],
            'provider_plan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->enrollmentService->finalizeProviderEnrollment(
            $enrollment,
            $validated['hmo_card_number'],
            $validated['provider_plan'] ?? null
        );

        return redirect()->route('hmo.enrollments', ['tab' => 'roster'])->with('status', "HMO coverage activated and Member Card #{$validated['hmo_card_number']} issued!");
    }

    /**
     * Reject HMO Application
     */
    public function rejectEnrollment(HmoEnrollment $enrollment, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $this->enrollmentService->rejectApplication($enrollment, $validated['rejection_reason']);

        return redirect()->route('hmo.enrollments', ['tab' => 'approvals'])->with('status', 'HMO application rejected and employee notified.');
    }

    /**
     * Step 6: 1-Click 30-Day Annual Renewal (known.md §8.6 Step 6)
     */
    public function renewEnrollment(HmoEnrollment $enrollment): RedirectResponse
    {
        $this->enrollmentService->processAnnualRenewal($enrollment);

        return redirect()->route('hmo.enrollments', ['tab' => 'roster'])->with('status', 'HMO policy coverage successfully renewed for 1 additional year.');
    }

    /**
     * Enroll an employee into HMO coverage
     */
    public function enroll(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'hmo_provider' => ['required', 'string', 'max:255'],
            'provider_plan' => ['required', 'string', 'max:255'],
            'coverage_tier' => ['required', 'string', 'in:Basic,Plus,Premium,Driver Fleet Care'],
            'coverage_start_date' => ['required', 'date'],
            'coverage_end_date' => ['required', 'date', 'after_or_equal:coverage_start_date'],
            'annual_limit' => ['required', 'numeric', 'min:0'],
            'monthly_premium' => ['required', 'numeric', 'min:0'],
            'dependent_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrollment = HmoEnrollment::create([
            'employee_id' => $validated['employee_id'],
            'hmo_card_number' => 'HMO-' . strtoupper(Str::random(8)),
            'hmo_provider' => $validated['hmo_provider'],
            'provider_plan' => $validated['provider_plan'],
            'coverage_tier' => $validated['coverage_tier'],
            'mbl_amount' => $validated['annual_limit'],
            'annual_limit' => $validated['annual_limit'],
            'monthly_premium' => $validated['monthly_premium'],
            'dependent_count' => (int) ($validated['dependent_count'] ?? 0),
            'coverage_start_date' => $validated['coverage_start_date'],
            'coverage_end_date' => $validated['coverage_end_date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'active',
        ]);

        return redirect()->route('hmo.enrollments', ['tab' => 'roster'])->with('status', 'Employee successfully enrolled into HMO coverage policy!');
    }

    /**
     * Update existing HMO enrollment
     */
    public function updateEnrollment(Request $request, HmoEnrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'hmo_provider' => ['required', 'string', 'max:255'],
            'provider_plan' => ['required', 'string', 'max:255'],
            'coverage_tier' => ['required', 'string', 'in:Basic,Plus,Premium,Driver Fleet Care'],
            'coverage_start_date' => ['required', 'date'],
            'coverage_end_date' => ['required', 'date', 'after_or_equal:coverage_start_date'],
            'annual_limit' => ['required', 'numeric', 'min:0'],
            'monthly_premium' => ['required', 'numeric', 'min:0'],
            'dependent_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'status' => ['required', 'string', 'in:active,inactive,expired'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrollment->update([
            'hmo_provider' => $validated['hmo_provider'],
            'provider_plan' => $validated['provider_plan'],
            'coverage_tier' => $validated['coverage_tier'],
            'mbl_amount' => $validated['annual_limit'],
            'annual_limit' => $validated['annual_limit'],
            'monthly_premium' => $validated['monthly_premium'],
            'dependent_count' => (int) ($validated['dependent_count'] ?? 0),
            'coverage_start_date' => $validated['coverage_start_date'],
            'coverage_end_date' => $validated['coverage_end_date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('hmo.enrollments', ['tab' => 'roster'])->with('status', 'HMO enrollment details successfully updated!');
    }

    /**
     * 4.8 Log Benefits Utilization & Decrement MBL Balance (known.md §8.8)
     */
    public function logUtilization(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'hmo_enrollment_id' => ['nullable', 'exists:hmo_enrollments,id'],
            'benefit_type' => ['nullable', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'service_provider' => ['nullable', 'string', 'max:255'],
            'hospital_clinic_name' => ['nullable', 'string', 'max:255'],
            'utilized_amount' => ['required', 'numeric', 'min:0.01'],
            'utilized_at' => ['nullable', 'date'],
            'service_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'diagnosis' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrollment = null;
        if (!empty($validated['hmo_enrollment_id'])) {
            $enrollment = HmoEnrollment::find($validated['hmo_enrollment_id']);
            $employeeId = $enrollment?->employee_id ?? $validated['employee_id'];
        } else {
            $employeeId = $validated['employee_id'];
            $enrollment = HmoEnrollment::where('employee_id', $employeeId)->where('status', 'active')->first();
        }

        $remaining = 0.00;
        if ($enrollment) {
            $currentUtilized = (float) $enrollment->utilizationLogs()->sum('utilized_amount');
            $limit = (float) ($enrollment->annual_limit ?: $enrollment->mbl_amount);
            $remaining = max(0.00, $limit - ($currentUtilized + (float) $validated['utilized_amount']));
        }

        $serviceProvider = $validated['hospital_clinic_name'] ?? $validated['service_provider'] ?? 'Accredited Medical Center';
        $benefitType = $validated['service_type'] ?? $validated['benefit_type'] ?? 'HMO Outpatient / Inpatient';
        $serviceDate = $validated['service_date'] ?? $validated['utilized_at'] ?? now()->toDateString();
        $description = $validated['diagnosis'] ?? $validated['description'] ?? null;

        HmoUtilizationLog::create([
            'employee_id' => $employeeId,
            'hmo_enrollment_id' => $enrollment?->id,
            'benefit_type' => $benefitType,
            'service_provider' => $serviceProvider,
            'utilized_amount' => $validated['utilized_amount'],
            'remaining_balance' => $remaining,
            'utilized_at' => $serviceDate,
            'description' => $description,
        ]);

        return redirect()->route('hmo.enrollments', ['tab' => 'roster'])
            ->with('status', 'Benefits utilization logged successfully. Remaining MBL balance decremented.');
    }

    /**
     * 4.5 Driver-Specific HMO & Accident Coverage (known.md §8.7 & §8.8)
     */
    public function driverInsurance(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = AccidentClaim::with('employee.department')->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($sub) use ($search) {
                    $sub->search($search);
                })->orWhere('incident_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('vehicle_plate_number', 'like', "%{$search}%");
            });
        }

        if ($status && $status !== 'all') {
            $query->where('workflow_status', $status);
        }

        $accidentClaims = $query->paginate(10)->withQueryString();
        $employees = Employee::where('position', 'like', '%Driver%')->orderBy('first_name')->get();

        $stats = $this->driverPoolService->getPoolSummary();
        $ledger = $this->driverPoolService->getPoolLedger(null, 10);

        return view('payroll-benefits.hmo.driver-insurance', compact(
            'accidentClaims',
            'employees',
            'stats',
            'ledger',
            'search',
            'status'
        ));
    }

    /**
     * File a new driver accident claim (known.md §8.7 Step 1)
     */
    public function fileClaim(DriverAccidentClaimRequest $request): RedirectResponse
    {
        $claim = $this->driverPoolService->fileClaim(
            (int) $request->validated('employee_id'),
            $request->validated(),
            $request->file('police_report'),
            $request->file('medical_receipt'),
            $request->file('incident_photo')
        );

        return redirect()->route('hmo.driver-insurance')->with('status', "Driver accident claim {$claim->incident_number} submitted for HR validation.");
    }

    /**
     * Step 1: HR Reviews & Validates Driver Accident Claim (known.md §8.7 Step 2)
     */
    public function accidentClaimApproveHr(Request $request, AccidentClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'min:0.01', 'max:' . $claim->bill_amount],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->driverPoolService->approveHr($claim, (float) $validated['approved_amount'], $validated['remarks'] ?? null);

        return redirect()->route('hmo.driver-insurance')->with('status', "Claim {$claim->incident_number} approved by HR and forwarded to Fleet Administrator.");
    }

    /**
     * Step 2: Administrator Reviews Driver Accident Claim (known.md §8.7 Step 3)
     */
    public function accidentClaimApproveAdmin(Request $request, AccidentClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->driverPoolService->approveAdmin($claim, $validated['remarks'] ?? null);

        return redirect()->route('hmo.driver-insurance')->with('status', "Claim {$claim->incident_number} approved by Admin and submitted to Financial Management (Team 5).");
    }

    /**
     * Step 3: Finance Validates Fund Availability & Releases Driver Claim (known.md §8.7 Step 4 & 5)
     */
    public function accidentClaimApproveFinance(Request $request, AccidentClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->driverPoolService->approveFinance($claim, $validated['remarks'] ?? null);

        return redirect()->route('hmo.driver-insurance')->with('status', "Claim {$claim->incident_number} approved and payout disbursed from Driver Pool.");
    }

    /**
     * Return Driver Accident Claim at any step
     */
    public function accidentClaimReturn(Request $request, AccidentClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $this->driverPoolService->returnClaim($claim, $validated['remarks']);

        return redirect()->route('hmo.driver-insurance')->with('status', "Claim {$claim->incident_number} returned for revision with remarks.");
    }

    /**
     * Update driver benefit contribution rate and company match settings (known.md §8.7)
     */
    public function updateDriverContributionRate(DriverPoolConfigRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $rate = (float) ($validated['contribution_rate'] ?? $validated['rate'] ?? 3.0);
        $match = (float) ($validated['company_match_pct'] ?? 50.0);

        $this->driverPoolService->updatePoolContributionRate($rate, $match);

        return redirect()->route('hmo.driver-insurance')->with('status', 'Driver Accident Insurance Pool contribution rate and company matching subsidy updated successfully.');
    }

    /**
     * Export Driver Insurance Pool Fund Accounting Ledger CSV (known.md §8.8)
     */
    public function exportPoolLedger(): StreamedResponse
    {
        return $this->driverPoolService->exportPoolLedgerCsv();
    }

    /**
     * 4.8 Benefit Type Master Catalog (Consolidated into Plans & Directory)
     */
    public function benefitTypes(Request $request): RedirectResponse
    {
        return redirect()->route('hmo.plans', ['tab' => 'catalog']);
    }

    /**
     * Store new or updated Benefit Type
     */
    public function storeBenefitType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'in:Health Insurance,Insurance,Government Mandated,Statutory,Allowance'],
            'eligibility' => ['required', 'string', 'max:255'],
            'min_tenure_months' => ['required', 'integer', 'min:0'],
            'dependent_options' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BenefitType::updateOrCreate(
            ['code' => $validated['code']],
            [
                'name' => $validated['name'],
                'category' => $validated['category'],
                'eligibility' => $validated['eligibility'],
                'min_tenure_months' => (int) $validated['min_tenure_months'],
                'dependent_options' => $validated['dependent_options'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) ($request->has('is_active')),
            ]
        );

        return redirect()->route('hmo.plans', ['tab' => 'catalog'])->with('status', 'Benefit package catalog updated successfully.');
    }

    /**
     * Toggle Benefit Type active status
     */
    public function toggleBenefitType(BenefitType $benefitType): RedirectResponse
    {
        $benefitType->update([
            'is_active' => !$benefitType->is_active,
        ]);

        $statusStr = $benefitType->is_active ? 'activated' : 'deactivated';
        return redirect()->route('hmo.plans', ['tab' => 'catalog'])->with('status', "Benefit package {$benefitType->name} {$statusStr}.");
    }

    /**
     * 4.12 Benefits Cost Tracking & Corporate Budget Hub (Consolidated Hub)
     */
    public function costTracking(Request $request): View
    {
        $departmentId = $request->query('department_id');
        $search = $request->query('search');
        $tab = $request->query('tab', 'tce');

        // Tab 1: Total Cost of Employment (TCE)
        $query = Employee::with(['department', 'hmoEnrollments' => function ($q) {
            $q->where('status', 'active');
        }]);

        if ($search) {
            $query->search($search);
        }

        if ($departmentId && $departmentId !== 'all') {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->paginate(12, ['*'], 'tce_page')->withQueryString();
        $departments = Department::orderBy('name')->get();

        // Calculate Total Cost of Employment per employee
        $tceData = $employees->map(function ($emp) {
            $isDriver = str_contains(strtolower($emp->position ?? ''), 'driver') ||
                        str_contains(strtolower($emp->department?->name ?? ''), 'fleet');

            $basicSalary = (float) ($emp->monthly_rate ?? 0);
            $allowances = 3500.00; // Standard company allowance package

            $activeEnrollment = $emp->hmoEnrollments->first();
            $hmoEmployerShare = $activeEnrollment ? (float) $activeEnrollment->monthly_premium : ($isDriver ? 300.00 : 900.00);

            // Statutory employer contributions (DOLE / SSS / PhilHealth / Pag-IBIG standard employer shares)
            $sssEmployer = $isDriver ? 0.00 : min(1900.00, round($basicSalary * 0.095, 2));
            $philhealthEmployer = $isDriver ? 0.00 : min(1250.00, round($basicSalary * 0.025, 2));
            $pagibigEmployer = $isDriver ? 0.00 : 200.00;

            $totalTce = $basicSalary + $allowances + $hmoEmployerShare + $sssEmployer + $philhealthEmployer + $pagibigEmployer;

            return [
                'employee' => $emp,
                'is_driver' => $isDriver,
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'hmo_premium' => $hmoEmployerShare,
                'sss_employer' => $sssEmployer,
                'philhealth_employer' => $philhealthEmployer,
                'pagibig_employer' => $pagibigEmployer,
                'total_tce' => $totalTce,
            ];
        });

        $totalCompanyTce = $tceData->sum('total_tce');
        $totalHmoEmployerCost = $tceData->sum('hmo_premium');
        $totalGovtEmployerCost = $tceData->sum(fn ($d) => $d['sss_employer'] + $d['philhealth_employer'] + $d['pagibig_employer']);

        // Departmental Aggregations for Executive Summary Cards
        $allEmployees = Employee::with(['department', 'hmoEnrollments' => function ($q) {
            $q->where('status', 'active');
        }])->get();

        $departmentSummaries = $allEmployees->groupBy(function ($emp) {
            return $emp->department?->name ?? 'General / Unassigned';
        })->map(function ($emps, $deptName) {
            $basic = $emps->sum(fn ($e) => (float) ($e->monthly_rate ?? 0));
            $allowances = $emps->count() * 3500.00;
            $hmo = $emps->sum(function ($e) {
                $isDriver = str_contains(strtolower($e->position ?? ''), 'driver');
                $active = $e->hmoEnrollments->first();
                return $active ? (float) $active->monthly_premium : ($isDriver ? 300.00 : 900.00);
            });
            $govt = $emps->sum(function ($e) {
                $isDriver = str_contains(strtolower($e->position ?? ''), 'driver');
                $salary = (float) ($e->monthly_rate ?? 0);
                if ($isDriver) return 0.00;
                return min(1900.00, round($salary * 0.095, 2)) + min(1250.00, round($salary * 0.025, 2)) + 200.00;
            });

            return [
                'department_name' => $deptName,
                'headcount' => $emps->count(),
                'total_basic' => $basic,
                'total_allowances' => $allowances,
                'total_hmo' => $hmo,
                'total_govt' => $govt,
                'total_tce' => $basic + $allowances + $hmo + $govt,
            ];
        });

        $stats = [
            'total_tce' => $totalCompanyTce,
            'total_hmo_cost' => $totalHmoEmployerCost,
            'total_govt_cost' => $totalGovtEmployerCost,
            'headcount' => $employees->total(),
        ];

        // Tab 2: Corporate Budget Requisitions & Real-time Spend Tracking (§4.11)
        $budgetStatus = $request->query('budget_status');
        $budgetCategory = $request->query('budget_category');

        $budgetQuery = BudgetRequisition::latest();

        if ($budgetStatus && $budgetStatus !== 'all') {
            $budgetQuery->where('status', $budgetStatus);
        }

        if ($budgetCategory && $budgetCategory !== 'all') {
            $budgetQuery->where('category', $budgetCategory);
        }

        $requisitions = $budgetQuery->paginate(10, ['*'], 'req_page')->withQueryString();

        $totalApprovedBudget = (float) BudgetRequisition::whereIn('status', ['approved', 'released'])->sum('amount');
        $totalDisbursedSpend = (float) BudgetRequisition::where('status', 'released')->sum('amount') +
                               (float) AccidentClaim::where('status', 'paid')->sum('approved_amount');
        $budgetPercentUsed = $totalApprovedBudget > 0 ? min(100, round(($totalDisbursedSpend / $totalApprovedBudget) * 100, 1)) : 0;
        $budgetRemaining = max(0.00, $totalApprovedBudget - $totalDisbursedSpend);

        $budgetStats = [
            'total_requested' => (float) BudgetRequisition::sum('amount'),
            'total_approved' => (float) BudgetRequisition::where('status', 'approved')->sum('amount'),
            'total_released' => (float) BudgetRequisition::where('status', 'released')->sum('amount'),
            'pending_count' => BudgetRequisition::where('status', 'awaiting_approval')->count(),
            'total_approved_budget' => $totalApprovedBudget,
            'total_disbursed_spend' => $totalDisbursedSpend,
            'percent_used' => $budgetPercentUsed,
            'remaining_balance' => $budgetRemaining,
        ];

        return view('payroll-benefits.hmo.cost-tracking', compact(
            'employees',
            'tceData',
            'departments',
            'departmentSummaries',
            'stats',
            'departmentId',
            'search',
            'requisitions',
            'budgetStats',
            'budgetStatus',
            'budgetCategory',
            'tab'
        ));
    }

    /**
     * Stream 1-Click Total Cost of Employment (TCE) Master CSV Export (§4.12)
     */
    public function exportTceCsv(): StreamedResponse
    {
        $employees = Employee::with(['department', 'hmoEnrollments' => function ($q) {
            $q->where('status', 'active');
        }])->orderBy('first_name')->get();

        $filename = 'total_cost_of_employment_master_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Employee Code',
                'Full Name',
                'Department',
                'Position',
                'Basic Monthly Salary (PHP)',
                'Allowances (PHP)',
                'Employer HMO Share (PHP)',
                'Employer SSS Share (PHP)',
                'Employer PhilHealth Share (PHP)',
                'Employer Pag-IBIG Share (PHP)',
                'Total Monthly Employer Cost (PHP)',
                'Annualized Total Cost (PHP)',
            ]);

            foreach ($employees as $emp) {
                $isDriver = str_contains(strtolower($emp->position ?? ''), 'driver') ||
                            str_contains(strtolower($emp->department?->name ?? ''), 'fleet');

                $basicSalary = (float) ($emp->monthly_rate ?? 0);
                $allowances = 3500.00;

                $activeEnrollment = $emp->hmoEnrollments->first();
                $hmoEmployerShare = $activeEnrollment ? (float) $activeEnrollment->monthly_premium : ($isDriver ? 300.00 : 900.00);

                $sssEmployer = $isDriver ? 0.00 : min(1900.00, round($basicSalary * 0.095, 2));
                $philhealthEmployer = $isDriver ? 0.00 : min(1250.00, round($basicSalary * 0.025, 2));
                $pagibigEmployer = $isDriver ? 0.00 : 200.00;

                $totalMonthlyCost = $basicSalary + $allowances + $hmoEmployerShare + $sssEmployer + $philhealthEmployer + $pagibigEmployer;
                $annualizedCost = $totalMonthlyCost * 12;

                fputcsv($file, [
                    $emp->employee_code,
                    $emp->first_name . ' ' . $emp->last_name,
                    $emp->department?->name ?? 'General',
                    $emp->position,
                    number_format($basicSalary, 2, '.', ''),
                    number_format($allowances, 2, '.', ''),
                    number_format($hmoEmployerShare, 2, '.', ''),
                    number_format($sssEmployer, 2, '.', ''),
                    number_format($philhealthEmployer, 2, '.', ''),
                    number_format($pagibigEmployer, 2, '.', ''),
                    number_format($totalMonthlyCost, 2, '.', ''),
                    number_format($annualizedCost, 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 4.10 / 4.13 Budget Requisition for HMO & Benefits Sub-Module (Redirect to consolidated Cost Tracking)
     */
    public function budgetRequests(Request $request): RedirectResponse
    {
        return redirect()->route('hmo.cost-tracking', array_merge(['tab' => 'budget'], $request->query()));
    }

    /**
     * Submit Budget Requisition to Financial Management (Team 5)
     */
    public function submitRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'justification' => ['required', 'string', 'max:1000'],
        ]);

        $code = 'REQ-' . now()->format('Y') . '-' . str_pad((string) rand(100, 999), 3, '0', STR_PAD_LEFT);

        BudgetRequisition::create([
            'requisition_code' => $code,
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'justification' => $validated['justification'],
            'status' => 'awaiting_approval',
        ]);

        return redirect()->route('hmo.cost-tracking', ['tab' => 'budget'])->with('status', "Budget requisition {$code} transmitted to Financial Management (Team 5).");
    }

    /**
     * Approve or update Budget Requisition status (Finance Team 5 stream)
     */
    public function updateBudgetRequestStatus(Request $request, BudgetRequisition $requisition): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:awaiting_approval,approved,released,rejected'],
        ]);

        $requisition->update(['status' => $validated['status']]);

        $label = match ($validated['status']) {
            'approved' => 'approved for allocation',
            'released' => 'marked as funds disbursed',
            'rejected' => 'rejected',
            default => 'updated',
        };

        return redirect()->route('hmo.cost-tracking', ['tab' => 'budget'])->with('status', "Budget Requisition {$requisition->requisition_code} {$label}.");
    }

    /**
     * Corporate Wellness, Annual Physical Exam & Group Life Administration (known.md §8.9 & §8.10)
     */
    public function corporateWellness(Request $request): View
    {
        $year = (int) $request->query('year', (int) date('Y'));
        $search = $request->query('search');
        $attendanceStatus = $request->query('attendance_status');

        $apeQuery = AnnualPhysicalExam::with('employee.department')->where('exam_year', $year)->latest();

        if ($search) {
            $apeQuery->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($attendanceStatus && $attendanceStatus !== 'all') {
            $apeQuery->where('attendance_status', $attendanceStatus);
        }

        $exams = $apeQuery->paginate(12, ['*'], 'ape_page')->withQueryString();
        $apeSummary = $this->wellnessService->getApeCampaignSummary($year);

        // Statutory Remittance Compliance Calendar
        $remittanceCalendar = $this->wellnessService->getComplianceRemittanceCalendar();

        $employees = Employee::where('employment_status', '!=', 'terminated')->orderBy('first_name')->get();
        $departments = Department::orderBy('name')->get();

        return view('payroll-benefits.hmo.corporate-wellness', compact(
            'exams',
            'apeSummary',
            'remittanceCalendar',
            'employees',
            'departments',
            'year',
            'search',
            'attendanceStatus'
        ));
    }

    /**
     * Schedule Individual Annual Physical Exam
     */
    public function scheduleApe(ApeScheduleRequest $request): RedirectResponse
    {
        $this->wellnessService->scheduleApe(
            (int) $request->validated('employee_id'),
            $request->validated()
        );

        return redirect()->route('hmo.corporate-wellness')->with('status', 'Annual Physical Exam scheduled successfully.');
    }

    /**
     * Batch Schedule APE Campaign for Department or Entire Workforce
     */
    public function batchScheduleApe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'schedule_date' => ['required', 'date'],
            'facility_name' => ['required', 'string', 'max:255'],
            'package_type' => ['required', 'string', 'in:Standard Occupational,Executive Comprehensive,Driver Road Fit'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $count = $this->wellnessService->batchScheduleApeCampaign(
            (int) $validated['exam_year'],
            $validated['schedule_date'],
            $validated['facility_name'],
            $validated['package_type'],
            $validated['department_id'] ? (int) $validated['department_id'] : null
        );

        return redirect()->route('hmo.corporate-wellness')->with('status', "Batch APE campaign generated! {$count} employees scheduled.");
    }

    /**
     * Record APE Attendance, Medical Clearance & Certificate
     */
    public function recordApeResults(ApeResultsRequest $request, AnnualPhysicalExam $exam): RedirectResponse
    {
        $this->wellnessService->recordApeResults(
            $exam,
            $request->validated(),
            $request->file('medical_certificate')
        );

        return redirect()->route('hmo.corporate-wellness')->with('status', "APE results and medical clearance recorded for {$exam->employee?->first_name} {$exam->employee?->last_name}.");
    }

    /**
     * Enroll Employee into Group Life & Disability Policy
     */
    public function enrollGroupLife(GroupLifeEnrollmentRequest $request): RedirectResponse
    {
        $policy = $this->wellnessService->enrollGroupLife(
            (int) $request->validated('employee_id'),
            $request->validated()
        );

        return redirect()->route('hmo.corporate-wellness')->with('status', "Group Life Policy #{$policy->policy_number} enrolled successfully.");
    }

    /**
     * Update Group Life Policy Beneficiaries
     */
    public function updateGroupLife(Request $request, GroupLifePolicy $policy): RedirectResponse
    {
        $validated = $request->validate([
            'beneficiary_primary_name' => ['required', 'string', 'max:255'],
            'beneficiary_primary_relation' => ['required', 'string', 'max:100'],
            'beneficiary_secondary_name' => ['nullable', 'string', 'max:255'],
            'beneficiary_secondary_relation' => ['nullable', 'string', 'max:100'],
            'sum_assured' => ['required', 'numeric', 'min:50000'],
        ]);

        $this->wellnessService->updateGroupLifeBeneficiaries($policy, $validated);

        return redirect()->route('hmo.corporate-wellness')->with('status', "Group Life Policy #{$policy->policy_number} beneficiaries updated.");
    }
}
