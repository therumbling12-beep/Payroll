<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Integrations\Team3PromotionSyncRequest;
use App\Models\CompanySetting;
use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use App\Services\Compensation\AuditTrailExportService;
// BonusPoolDistributionService removed — Phase 4 (docs/no.md: bonuses N/A)
use App\Services\Compensation\CompensationApprovalService;
use App\Services\Compensation\CounterOfferService;
use App\Services\Compensation\MeritIncreaseService;
use App\Services\Compensation\ProbationaryConversionService;
use App\Services\Compensation\RetroactivePayCalculationService;
use App\Services\Compensation\SalaryDeterminationService;
use App\Services\Compensation\TenureProgressionService;
use App\Services\FinancialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompensationController extends Controller
{
    public function __construct(
        protected FinancialService $financialService,
        protected CounterOfferService $counterOfferService,
        protected SalaryDeterminationService $salaryDeterminationService,
        protected MeritIncreaseService $meritIncreaseService,
        protected RetroactivePayCalculationService $retroactivePayCalculationService,
        protected TenureProgressionService $tenureProgressionService,
        // ProbationaryConversionService decommissioned from controller (Phase 2)
        protected CompensationApprovalService $compensationApprovalService,
        protected AuditTrailExportService $auditTrailExportService
    ) {}

    /**
     * Salary Band Management Sub-Module (Section 2.2 & Prof Note #5)
     */
    public function salaryBands(Request $request): View
    {
        $search = $request->query('search');
        $deptId = $request->query('department');

        $query = Employee::with('department')->where('employment_status', '!=', 'resigned');

        if ($search) {
            $query->search($search);
        }

        if ($deptId && $deptId !== 'all') {
            $query->department($deptId);
        }

        $employees = $query->paginate(12)->withQueryString();
        $departments = Department::all();
        $salaryGrades = SalaryGrade::with('steps')->orderBy('id')->get();

        // Retrieve band change audit logs (Section 2.2 History Log)
        $bandHistory = PayrollAuditTrail::whereIn('action', ['SALARY_BAND_UPDATE', 'SALARY_BAND_BULK_ADJUST'])
            ->latest()
            ->take(15)
            ->get();

        $localityMinimumWage = (float) CompanySetting::getValue('minimum_wage_daily', 755.00);
        $localityMonthlyFloor = round($localityMinimumWage * 26.0, 2);

        return view('payroll-benefits.compensation.salary-bands', compact(
            'employees',
            'departments',
            'salaryGrades',
            'bandHistory',
            'search',
            'deptId',
            'localityMinimumWage',
            'localityMonthlyFloor'
        ));
    }

    /**
     * Determine Recommended Starting Salary based on 6-Factor Candidate Scoring (docs/no.md Lines 25–35)
     */
    public function determineSalary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'salary_grade_id' => 'nullable|exists:salary_grades,id',
            'experience' => 'required|integer|min:1|max:6',
            'skills' => 'required|integer|min:1|max:6',
            'education' => 'required|integer|min:1|max:6',
            'certifications' => 'nullable|integer|min:1|max:6',
            'previous_salary' => 'nullable|integer|min:1|max:6',
            'interview_performance' => 'nullable|integer|min:1|max:6',
            'market_benchmark' => 'nullable|integer|min:1|max:6',
            'internal_equity' => 'nullable|integer|min:1|max:6',
        ]);

        $grade = ! empty($validated['salary_grade_id'])
            ? SalaryGrade::find($validated['salary_grade_id'])
            : SalaryGrade::first();

        $result = $this->salaryDeterminationService->calculateRecommendedSalary($grade, $validated);

        return response()->json($result);
    }

    /**
     * Direct Merit Increase without Formal Promotion (docs/no.md Line 51)
     */
    public function applyDirectMeritIncrease(Request $request, Employee $employee): RedirectResponse
    {
        $localityMinWageDaily = (float) CompanySetting::getValue('minimum_wage_daily', 755.00);
        $localityMinWageMonthly = round($localityMinWageDaily * 26.0, 2); // 19,630.00

        $validated = $request->validate([
            'new_daily_rate' => ['nullable', 'numeric', "min:{$localityMinWageDaily}"],
            'new_monthly_rate' => ['nullable', 'numeric', "min:{$localityMinWageMonthly}"],
            'increase_percentage' => ['nullable', 'numeric', 'min:0.1', 'max:100.0'],
            'effective_date' => ['nullable', 'date'],
            'justification' => ['required', 'string', 'max:1000'],
        ]);

        $oldDaily = (float) $employee->daily_rate;
        $oldMonthly = (float) $employee->monthly_rate;

        $newDaily = isset($validated['new_daily_rate']) ? (float) $validated['new_daily_rate'] : null;
        $newMonthly = isset($validated['new_monthly_rate']) ? (float) $validated['new_monthly_rate'] : null;

        if (! $newDaily && ! $newMonthly && isset($validated['increase_percentage'])) {
            $pct = (float) $validated['increase_percentage'];
            $newDaily = round($oldDaily * (1 + ($pct / 100)), 2);
            $newMonthly = round($oldMonthly * (1 + ($pct / 100)), 2);
        } elseif ($newDaily && ! $newMonthly) {
            $newMonthly = round($newDaily * 26.0, 2);
        } elseif ($newMonthly && ! $newDaily) {
            $newDaily = round($newMonthly / 26.0, 2);
        }

        $newDaily = max($localityMinWageDaily, (float) $newDaily);
        $newMonthly = max($localityMinWageMonthly, (float) $newMonthly);

        DB::transaction(function () use ($employee, $oldDaily, $oldMonthly, $newDaily, $newMonthly, $validated) {
            $employee->daily_rate = $newDaily;
            $employee->monthly_rate = $newMonthly;
            $employee->save();

            CompensationAdjustment::create([
                'employee_id' => $employee->id,
                'type' => 'merit_promotion',
                'old_rate' => $oldMonthly ?: ($oldDaily * 26.0),
                'new_rate' => $newMonthly,
                'status' => 'approved',
                'effective_date' => $validated['effective_date'] ?? now(),
                'reason' => "Direct Merit Increase: {$validated['justification']}",
            ]);

            PayrollAuditTrail::create([
                'user_name' => auth()->user()?->name ?? 'HR Compensation Manager',
                'action' => 'MERIT_INCREASE_APPLIED',
                'model_type' => Employee::class,
                'model_id' => $employee->id,
                'old_values' => [
                    'daily_rate' => $oldDaily,
                    'monthly_rate' => $oldMonthly,
                ],
                'new_values' => [
                    'daily_rate' => $newDaily,
                    'monthly_rate' => $newMonthly,
                    'justification' => $validated['justification'],
                    'effective_date' => $validated['effective_date'] ?? now()->toDateString(),
                ],
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);
        });

        return redirect()->back()->with('status', "Direct merit increase successfully applied for [{$employee->first_name} {$employee->last_name}] (New Rate: PHP " . number_format($newDaily, 2) . "/day • PHP " . number_format($newMonthly, 2) . "/mo).");
    }

    /**
     * Update an individual salary band
     */
    public function updateSalaryBand(SalaryGrade $grade, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'min_salary' => 'required|numeric|min:0',
            'max_salary' => 'required|numeric|gt:min_salary',
            'annual_growth_rate' => 'nullable|numeric|min:0|max:100',
            'effectivity_date' => 'nullable|date',
        ]);

        $this->salaryDeterminationService->updateSalaryBand(
            $grade,
            (float) $validated['min_salary'],
            (float) $validated['max_salary'],
            isset($validated['annual_growth_rate']) ? (float) $validated['annual_growth_rate'] : null,
            $validated['effectivity_date'] ?? null
        );

        return redirect()->back()->with('status', "Salary Band for '{$grade->position_name}' updated successfully with effectivity date logged.");
    }

    /**
     * Bulk adjust all salary bands by a market percentage
     */
    public function bulkAdjustBands(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'percentage' => 'required|numeric|min:0.1|max:50',
        ]);

        $result = $this->salaryDeterminationService->bulkAdjustBands((float) $validated['percentage']);

        return redirect()->back()->with('status', "Applied +{$result['percentage']}% Annual Market Adjustment across {$result['updated_grades_count']} job salary grades.");
    }

    /**
     * Counter Offers & Applicant Salary Offer Packages (Sections 2.3, 2.6, 2.13 & Prof Notes #1, #2, #7)
     */
    public function counterOffers(Request $request): View
    {
        $search = $request->query('search');

        $query = CompensationAdjustment::with('employee.department')
            ->where('type', 'counter_offer')
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('applicant_name', 'like', "%{$search}%")
                    ->orWhere('competitor_company', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->search($search);
                    });
            });
        }

        $adjustments = $query->paginate(10)->withQueryString();
        $employees = Employee::with('department')->orderBy('first_name')->get();
        $salaryGrades = SalaryGrade::with('steps')->get();

        return view('payroll-benefits.compensation.counter-offers', compact(
            'adjustments',
            'employees',
            'salaryGrades',
            'search'
        ));
    }

    /**
     * Calculate Counter Offer, Total Cost to Company (CTC), and Wage Distortion (known.md §6.6, §6.9)
     */
    public function calculateCounterOffer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => 'required|string|in:mode_a,mode_b',
            'salary_grade_id' => 'required|exists:salary_grades,id',
            'employee_id' => 'nullable|exists:employees,id',
            'competitor_offer' => 'nullable|numeric|min:0',
            'education' => 'nullable|integer|min:1|max:6',
            'experience' => 'nullable|integer|min:1|max:6',
            'skills' => 'nullable|integer|min:1|max:6',
            'market_benchmark' => 'nullable|integer|min:1|max:6',
            'internal_equity' => 'nullable|integer|min:1|max:6',
            'basic_salary' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'comms_allowance' => 'nullable|numeric|min:0',
            'signing_bonus' => 'nullable|numeric|min:0',
        ]);

        $grade = SalaryGrade::findOrFail($validated['salary_grade_id']);
        $employee = ! empty($validated['employee_id']) ? Employee::find($validated['employee_id']) : null;

        if ($validated['mode'] === 'mode_a') {
            $factors = [
                'education' => (int) ($validated['education'] ?? 3),
                'experience' => (int) ($validated['experience'] ?? 2),
                'skills' => (int) ($validated['skills'] ?? 3),
                'market_benchmark' => (int) ($validated['market_benchmark'] ?? 3),
                'internal_equity' => (int) ($validated['internal_equity'] ?? 3),
            ];
            $result = $this->counterOfferService->computeModeA(
                $grade,
                (float) ($validated['competitor_offer'] ?? 0.0),
                $factors,
                $employee
            );
        } else {
            $result = $this->counterOfferService->computeModeB(
                $grade,
                (float) ($validated['basic_salary'] ?? $grade->min_salary),
                (float) ($validated['transport_allowance'] ?? 0.0),
                (float) ($validated['meal_allowance'] ?? 0.0),
                (float) ($validated['comms_allowance'] ?? 0.0),
                (float) ($validated['signing_bonus'] ?? 0.0),
                $employee
            );
        }

        return response()->json($result);
    }

    /**
     * Submit a new Compensation Adjustment (Merit, Counter Offer, etc)
     */
    public function storeAdjustment(Request $request): RedirectResponse
    {
        $subjectType = $request->input('subject_type', 'employee');

        $validated = $request->validate([
            'subject_type' => 'nullable|string|in:employee,applicant',
            'employee_id' => $subjectType === 'applicant' ? 'nullable' : 'required|exists:employees,id',
            'applicant_name' => $subjectType === 'applicant' ? 'required|string|max:255' : 'nullable|string|max:255',
            'applicant_position' => $subjectType === 'applicant' ? 'required|string|max:255' : 'nullable|string|max:255',
            'type' => 'required|string|in:merit_promotion,counter_offer,salary_config',
            'mode' => 'nullable|string|in:mode_a,mode_b',
            'new_rate' => 'nullable|numeric|min:0',
            'bonus_amount' => 'nullable|numeric|min:0',
            'signing_bonus' => 'nullable|numeric|min:0',
            'allowance_amount' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'comms_allowance' => 'nullable|numeric|min:0',
            'hmo_tier' => 'nullable|string|max:100',
            'new_position' => 'nullable|string|max:255',
            'competitor_company' => 'nullable|string|max:255',
            'competitor_offer' => 'nullable|numeric|min:0',
            'expected_salary' => 'nullable|numeric|min:0',
            'education_level' => 'nullable|string|max:100',
            'performance_rating' => 'nullable|string|max:100',
            'urgency_days' => 'nullable|integer|min:1|max:30',
            'reason' => 'required|string|max:1000',
            'years_experience' => 'nullable|integer|min:0',
            'certifications_count' => 'nullable|integer|min:0',
        ]);

        $employee = ! empty($validated['employee_id']) ? Employee::find($validated['employee_id']) : null;
        $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
        $isDriver = $employee ? str_contains(strtolower($employee->position ?? ''), 'driver') : false;
        $oldRate = $employee ? ($isDriver ? ($employee->daily_rate * $workingDays) : $employee->monthly_rate) : 0.00;

        $targetPosition = $validated['new_position']
            ?? ($employee ? $employee->position : ($validated['applicant_position'] ?? 'Staff'));

        $newRate = ! empty($validated['new_rate']) ? (float) $validated['new_rate'] : null;

        $transport = (float) ($validated['transport_allowance'] ?? 0.0);
        $meal = (float) ($validated['meal_allowance'] ?? 0.0);
        $comms = (float) ($validated['comms_allowance'] ?? 0.0);
        $allowancesTotal = (float) ($validated['allowance_amount'] ?? ($transport + $meal + $comms));
        $signingBonus = (float) ($validated['signing_bonus'] ?? 0.0);

        // Auto Compute Counter Offer if credentials provided and new_rate is empty
        if ($validated['type'] === 'counter_offer' && ! $newRate) {
            $grade = SalaryGrade::where('position_name', $targetPosition)->first() ?? SalaryGrade::first();
            $computed = $this->counterOfferService->computeModeA(
                $grade,
                (float) ($validated['competitor_offer'] ?? ($validated['expected_salary'] ?? 0.0)),
                [
                    'education' => 3,
                    'experience' => (int) ($validated['years_experience'] ?? 2),
                    'skills' => 3,
                    'market_benchmark' => 3,
                    'internal_equity' => 3,
                ],
                $employee
            );
            $newRate = (float) $computed['proposed_base_salary'];
        }

        $targetRate = $newRate ?? $oldRate;
        $deptName = $employee?->department?->name ?? 'Human Resources';

        // Calculate CTC
        $ctcData = $this->counterOfferService->calculateTotalCostToCompany($targetRate, $allowancesTotal, $signingBonus);

        // Evaluate Internal Equity
        $equityData = $this->counterOfferService->evaluateInternalEquity($targetPosition, $targetRate, $employee?->id);

        // Perform Financial Budget Check (Team 5 Integration)
        $budgetCheck = $this->financialService->checkBudgetAvailability($targetRate, $deptName);
        $status = $budgetCheck['approved'] ? 'pending' : 'rejected_financial_budget';
        $budgetImpactStatus = $budgetCheck['approved'] ? 'BUDGET_APPROVED' : 'BUDGET_REJECTED';

        CompensationAdjustment::create([
            'employee_id' => $employee?->id,
            'subject_type' => $validated['subject_type'] ?? ($employee ? 'employee' : 'applicant'),
            'applicant_name' => $validated['applicant_name'] ?? null,
            'applicant_position' => $validated['applicant_position'] ?? null,
            'type' => $validated['type'],
            'mode' => $validated['mode'] ?? 'mode_a',
            'old_rate' => $oldRate,
            'new_rate' => $targetRate,
            'monthly_ctc' => $ctcData['monthly_ctc'],
            'annual_ctc' => $ctcData['annual_ctc'],
            'thirteenth_month_liability' => $ctcData['thirteenth_month_liability'],
            'employer_statutory_total' => $ctcData['employer_statutory']['total'],
            'bonus_amount' => $validated['bonus_amount'] ?? 0.00,
            'signing_bonus' => $signingBonus,
            'allowance_amount' => $allowancesTotal,
            'transport_allowance' => $transport,
            'meal_allowance' => $meal,
            'comms_allowance' => $comms,
            'hmo_tier' => $validated['hmo_tier'] ?? 'Individual',
            'old_position' => $employee?->position ?? ($validated['applicant_position'] ?? $targetPosition),
            'new_position' => $targetPosition,
            'competitor_company' => $validated['competitor_company'] ?? null,
            'competitor_offer' => $validated['competitor_offer'] ?? null,
            'expected_salary' => $validated['expected_salary'] ?? null,
            'education_level' => $validated['education_level'] ?? null,
            'internal_equity_status' => $equityData['status'],
            'peer_median_salary' => $equityData['peer_median_salary'],
            'wage_distortion_variance_pct' => $equityData['variance_percentage'],
            'budget_impact_status' => $budgetImpactStatus,
            'urgency_days' => $validated['urgency_days'] ?? 7,
            'reason' => $validated['reason'].($budgetCheck['approved'] ? '' : " | Failed Financial Budget: {$budgetCheck['reason']}"),
            'status' => $status,
            'employee_response' => 'pending_response',
            'effective_date' => now(),
        ]);

        if (! $budgetCheck['approved']) {
            return redirect()->back()->with('error', "Counter offer proposal saved but marked as REJECTED (FINANCIAL BUDGET): {$budgetCheck['reason']}");
        }

        return redirect()->back()->with('status', 'Counter offer proposal created successfully with Total Cost to Company (CTC) computed and submitted for executive approval.');
    }

    /**
     * Update Employee Decision Response on a Counter Offer (Section 2.13)
     */
    public function updateResponse(CompensationAdjustment $adjustment, Request $request): RedirectResponse
    {
        $response = $request->input('employee_response') ?? $request->input('response', 'pending');
        $this->compensationApprovalService->recordEmployeeResponse($adjustment, (string) $response);

        $statusMsg = match (strtolower((string) $response)) {
            'accepted' => 'Employee accepted offer! Salary successfully locked and synced to Payroll.',
            'declined' => 'Employee declined offer. Offboarding workflow triggered for Team 1.',
            'negotiating' => 'Offer marked as currently negotiating with employee.',
            default => 'Response status updated.',
        };

        return redirect()->back()->with('status', $statusMsg);
    }

    /**
     * Calculate 5-Tier Merit Increase or Promotion Proposal (known.md §6.4, §6.7)
     */
    public function calculateMeritProposal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|string|in:merit,promotion',
            'custom_percentage' => 'nullable|numeric|min:0|max:30',
            'new_grade_id' => 'nullable|exists:salary_grades,id',
        ]);

        $employee = Employee::with('department')->findOrFail($validated['employee_id']);

        if ($validated['type'] === 'promotion' && ! empty($validated['new_grade_id'])) {
            $newGrade = SalaryGrade::findOrFail($validated['new_grade_id']);
            $result = $this->meritIncreaseService->computePromotion($employee, $newGrade);
        } else {
            $customPct = isset($validated['custom_percentage']) ? (float) $validated['custom_percentage'] : null;
            $result = $this->meritIncreaseService->computeMeritIncrease($employee, $customPct);
        }

        return response()->json($result);
    }

    /**
     * Calculate Retroactive Pay Differential based on Daily Rate and Days Worked (known.md §6.8)
     */
    public function calculateRetroactivePay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'new_monthly_rate' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'days_worked' => 'required|integer|min:1|max:60',
            'inject_to_cutoff' => 'nullable|string',
            'inject_to_cutoff_id' => 'nullable',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $result = $this->retroactivePayCalculationService->calculateRetroactiveDifferential(
            $employee,
            (float) $validated['new_monthly_rate'],
            $validated['effective_date'],
            (int) $validated['days_worked']
        );

        $cutoffPeriod = $validated['inject_to_cutoff'] ?? (is_string($request->input('inject_to_cutoff_id')) ? $request->input('inject_to_cutoff_id') : null);

        if (! empty($cutoffPeriod)) {
            $injected = $this->retroactivePayCalculationService->injectRetroactivePayToPayroll(
                $employee,
                (float) $result['retroactive_pay'],
                (string) $cutoffPeriod
            );
            $result['injected_to_payroll'] = $injected;
        }

        return response()->json($result);
    }

    /**
     * Merit & Promotions Sub-Module (Sections 2.9 & 2.10 & Prof Notes #3, #4)
     */
    public function meritPromotions(Request $request): View
    {
        $search = $request->query('search');
        $deptId = $request->query('department');

        $query = CompensationAdjustment::with('employee.department')
            ->where('type', 'merit_promotion')
            ->latest();

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->search($search);
            });
        }

        if ($deptId && $deptId !== 'all') {
            $query->whereHas('employee', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        $adjustments = $query->paginate(10)->withQueryString();
        $employees = Employee::with('department')->orderBy('first_name')->get();
        $departments = Department::all();
        $salaryGrades = SalaryGrade::with('steps')->get();
        $tenureProgressionService = $this->tenureProgressionService;

        // Query pending Team 3 approved promotion orders (Handshake Integration)
        $team3Promotions = CompensationAdjustment::where('type', 'merit_promotion')
            ->whereIn('status', ['approved_by_team_3', 'pending_payroll_sync'])
            ->get()
            ->keyBy('employee_id');

        return view('payroll-benefits.compensation.merit-promotions', compact(
            'adjustments',
            'employees',
            'departments',
            'salaryGrades',
            'tenureProgressionService',
            'team3Promotions',
            'search',
            'deptId'
        ));
    }

    /**
     * Inbound Webhook / Integration Ingress: Team 3 (Talent Management Approved Promotion Order)
     */
    public function syncTeam3Promotion(Team3PromotionSyncRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $employee = Employee::where('employee_code', $validated['employee_code'])->firstOrFail();
        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver');
        $currentSalary = (float)($isDriver ? ($employee->daily_rate * 26) : ($employee->monthly_rate ?: 25000.00));

        $targetGrade = isset($validated['target_grade_code'])
            ? SalaryGrade::where('grade_code', $validated['target_grade_code'])->first()
            : null;

        $minGradeFloor = $targetGrade ? (float)$targetGrade->min_salary : ($currentSalary * 1.15);
        $fifteenPctFloor = round($currentSalary * 1.15, 2);
        $promotedSalary = max($minGradeFloor, $fifteenPctFloor);
        $incrementalMonthlySalary = max(0.0, $promotedSalary - $currentSalary);
        $incrementalMonthlyCtc = round($incrementalMonthlySalary * 1.135, 2);

        $adjustment = CompensationAdjustment::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'type' => 'merit_promotion',
            ],
            [
                'subject_type' => 'employee',
                'status' => 'approved_by_team_3',
                'old_position' => $employee->position,
                'new_position' => $validated['promoted_position'],
                'old_rate' => $currentSalary,
                'new_rate' => $promotedSalary,
                'effective_date' => $validated['effective_date'],
                'reason' => 'Team 3 Promotion Order #' . $validated['promotion_order_number'] . ($request->filled('reason') ? ' — ' . $validated['reason'] : ''),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Team 3 promotion order successfully received and queued for payroll calibration',
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->first_name . ' ' . $employee->last_name,
            'current_position' => $employee->position,
            'promoted_position' => $validated['promoted_position'],
            'target_grade_code' => $targetGrade?->grade_code ?? 'N/A',
            'current_salary' => $currentSalary,
            'staged_promoted_salary' => $promotedSalary,
            'monthly_incremental_ctc' => $incrementalMonthlyCtc,
            'adjustment_id' => $adjustment->id,
        ], 200);
    }

    /**
     * Complete and approve all selected or pending Merit Planning Adjustments
     */
    public function completeMeritPlanning(Request $request): RedirectResponse
    {
        $plansJson = $request->input('plans_json');
        $plans = [];

        if (is_string($plansJson)) {
            $decoded = json_decode($plansJson, true);
            if (is_array($decoded)) {
                $plans = $decoded;
            }
        } elseif (is_array($request->input('plans'))) {
            $plans = $request->input('plans');
        }

        $adjustmentIds = $request->input('adjustment_ids', []);
        $updatedCount = 0;

        DB::transaction(function () use ($plans, $adjustmentIds, &$updatedCount) {
            // 1. Process simulated plans directly from Alpine state
            if (! empty($plans)) {
                foreach ($plans as $plan) {
                    $empId = $plan['id'] ?? ($plan['employee_id'] ?? null);
                    if (! $empId) {
                        continue;
                    }

                    $emp = Employee::find($empId);
                    if (! $emp) {
                        continue;
                    }

                    $workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
                    $staffDefault = (float) CompanySetting::getValue('staff_default_baseline_salary', 25000.00);
                    $oldSalary = (float) ($emp->monthly_rate ?: ($emp->daily_rate ? $emp->daily_rate * $workingDays : $staffDefault));
                    $meritPct = (float) ($plan['merit_pct'] ?? $plan['raise_pct'] ?? 0.0);
                    $tenurePct = (float) ($plan['tenure_pct'] ?? 0.0);
                    $raisePct = (float) ($plan['raise_pct'] ?? ($meritPct + $tenurePct));
                    $rating = (string) ($plan['rating'] ?? $emp->performance_rating ?? 'Satisfactory');
                    $currentStep = (int) ($plan['current_step'] ?? $emp->current_step ?? 1);
                    $nextStep = (int) ($plan['next_step'] ?? min(7, $currentStep + 1));
                    $newSalary = isset($plan['new_salary']) && (float) $plan['new_salary'] > 0
                        ? (float) $plan['new_salary']
                        : round($oldSalary * (1 + ($raisePct / 100)), 2);

                    $newPosition = $plan['new_position'] ?? ($plan['promoted_position'] ?? null);
                    $isPromotion = ! empty($plan['is_promoted']) || ($newPosition && $newPosition !== $emp->position);
                    $effectiveDate = isset($plan['effective_date']) ? \Carbon\Carbon::parse($plan['effective_date']) : now();

                    if ($newSalary <= 0 || ($newSalary <= $oldSalary && $raisePct <= 0 && ! $isPromotion)) {
                        continue;
                    }

                    $oldPosition = $emp->position;
                    $isDriver = str_contains(strtolower($newPosition ?: ($oldPosition ?? '')), 'driver');
                    $dailyRate = round($newSalary / $workingDays, 2);

                    $empUpdates = [
                        'monthly_rate' => $newSalary,
                    ];

                    if ($isDriver) {
                        $empUpdates['daily_rate'] = $dailyRate;
                    }

                    if ($isPromotion) {
                        $empUpdates['position'] = $newPosition;
                        $empUpdates['current_step'] = 1;
                    } elseif ($tenurePct > 0 && $currentStep < 7) {
                        $empUpdates['current_step'] = $nextStep;
                    }

                    $emp->update($empUpdates);

                    $monthlyCtc = (float) ($plan['monthly_ctc'] ?? round($newSalary * 1.135, 2));
                    $annualCtc = (float) ($plan['annual_ctc'] ?? round($monthlyCtc * 12, 2));

                    if ($isPromotion) {
                        $existingPromo = CompensationAdjustment::where('employee_id', $emp->id)
                            ->where('type', 'merit_promotion')
                            ->whereIn('status', ['approved_by_team_3', 'pending_payroll_sync', 'pending'])
                            ->latest()
                            ->first();

                        if ($existingPromo) {
                            $existingPromo->update([
                                'status' => 'synced_to_payroll',
                                'old_rate' => $oldSalary,
                                'new_rate' => $newSalary,
                                'monthly_ctc' => $monthlyCtc,
                                'annual_ctc' => $annualCtc,
                                'thirteenth_month_liability' => round($newSalary / 12, 2),
                                'employer_statutory_total' => round($monthlyCtc - $newSalary, 2),
                                'old_position' => $oldPosition,
                                'new_position' => $newPosition,
                                'budget_impact_status' => 'BUDGET_APPROVED',
                                'admin_approval_status' => 'ADMIN_APPROVED',
                                'effective_date' => $effectiveDate,
                            ]);
                        } else {
                            CompensationAdjustment::create([
                                'employee_id' => $emp->id,
                                'subject_type' => 'employee',
                                'type' => 'promotion',
                                'mode' => 'mode_a',
                                'old_rate' => $oldSalary,
                                'new_rate' => $newSalary,
                                'monthly_ctc' => $monthlyCtc,
                                'annual_ctc' => $annualCtc,
                                'thirteenth_month_liability' => round($newSalary / 12, 2),
                                'employer_statutory_total' => round($monthlyCtc - $newSalary, 2),
                                'old_position' => $oldPosition,
                                'new_position' => $newPosition,
                                'status' => 'approved',
                                'budget_impact_status' => 'BUDGET_APPROVED',
                                'admin_approval_status' => 'ADMIN_APPROVED',
                                'reason' => "Promotion to {$newPosition} with {$raisePct}% compensation advancement",
                                'effective_date' => $effectiveDate,
                            ]);
                        }
                    } else {
                        if ($tenurePct > 0) {
                            CompensationAdjustment::create([
                                'employee_id' => $emp->id,
                                'subject_type' => 'employee',
                                'type' => 'step_increment',
                                'mode' => 'mode_a',
                                'old_rate' => $oldSalary,
                                'new_rate' => $newSalary,
                                'monthly_ctc' => $monthlyCtc,
                                'annual_ctc' => $annualCtc,
                                'status' => 'approved',
                                'budget_impact_status' => 'BUDGET_APPROVED',
                                'admin_approval_status' => 'ADMIN_APPROVED',
                                'reason' => "Tenure longevity step advance to Step {$nextStep} (+{$tenurePct}%) via Unified Progression Desk",
                                'effective_date' => $effectiveDate,
                            ]);
                        }

                        CompensationAdjustment::create([
                            'employee_id' => $emp->id,
                            'subject_type' => 'employee',
                            'type' => 'merit_increase',
                            'mode' => 'mode_a',
                            'old_rate' => $oldSalary,
                            'new_rate' => $newSalary,
                            'monthly_ctc' => $monthlyCtc,
                            'annual_ctc' => $annualCtc,
                            'thirteenth_month_liability' => round($newSalary / 12, 2),
                            'employer_statutory_total' => round($monthlyCtc - $newSalary, 2),
                            'old_position' => $emp->position,
                            'new_position' => $emp->position,
                            'status' => 'approved',
                            'budget_impact_status' => 'BUDGET_APPROVED',
                            'admin_approval_status' => 'ADMIN_APPROVED',
                            'reason' => "5-Tier Merit Increase of {$meritPct}% based on Team 3 rating: {$rating}" . ($tenurePct > 0 ? " (Combined with Tenure Step {$nextStep} +{$tenurePct}%)" : ''),
                            'effective_date' => $effectiveDate,
                        ]);
                    }

                    // Check and execute retroactive pay injection if effective date precedes today
                    if ($effectiveDate->isPast() && $effectiveDate->diffInDays(now()) >= 1) {
                        $daysWorked = (int) $effectiveDate->diffInDays(now());
                        $retroDiff = $this->retroactivePayCalculationService->calculateRetroactiveDifferential(
                            $emp,
                            $newSalary,
                            $effectiveDate->format('Y-m-d'),
                            $daysWorked
                        );

                        $cutoffPeriod = SalaryComputation::where('employee_id', $emp->id)->latest('cutoff_period')->value('cutoff_period') ?? '2026-08-13_19';
                        $this->retroactivePayCalculationService->injectRetroactivePayToPayroll(
                            $emp,
                            (float) $retroDiff['retroactive_pay'],
                            $cutoffPeriod
                        );
                    }

                    PayrollAuditTrail::create([
                        'action' => $isPromotion ? 'PROMOTION_BATCH_COMMITTED' : 'MERIT_PROMOTION_BATCH_COMMITTED',
                        'model_type' => Employee::class,
                        'model_id' => $emp->id,
                        'user_name' => 'HR Manager',
                        'ip_address' => request()->ip() ?? '127.0.0.1',
                        'old_values' => ['monthly_rate' => $oldSalary, 'current_step' => $currentStep, 'position' => $oldPosition],
                        'new_values' => [
                            'monthly_rate' => $newSalary,
                            'merit_pct' => $meritPct,
                            'tenure_pct' => $tenurePct,
                            'raise_pct' => $raisePct,
                            'current_step' => $isPromotion ? 1 : (($tenurePct > 0 && $currentStep < 7) ? $nextStep : $currentStep),
                            'position' => $isPromotion ? $newPosition : $emp->position,
                        ],
                    ]);

                    $updatedCount++;
                }
            }

            // 2. Process any pending CompensationAdjustment records
            $query = CompensationAdjustment::with('employee')
                ->whereIn('type', ['merit_promotion', 'merit_increase'])
                ->where('status', 'pending');

            if (! empty($adjustmentIds)) {
                $query->whereIn('id', $adjustmentIds);
            }

            $adjustments = $query->get();

            foreach ($adjustments as $adjustment) {
                $synced = $this->compensationApprovalService->finalizeAndSyncToPayroll($adjustment);
                if ($synced) {
                    $updatedCount++;
                }
            }
        });

        return redirect()->back()->with('status', "Successfully approved and committed merit increases for {$updatedCount} personnel! Master salary records updated.");
    }

    /**
     * Calculate Next Tenure Step for Employee API (known.md §6.5)
     */
    public function calculateTenureStep(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $employee = Employee::with('department')->findOrFail($validated['employee_id']);
        $result = $this->tenureProgressionService->computeNextStep($employee);

        return response()->json($result);
    }

    // calculateBonusDistribution(), bonusAllocation(), storeBonusAllocation() removed — Phase 4 (docs/no.md: bonuses N/A)

    /**
     * Tenure Step Grid & Management (Section 2.12 & Prof Note #6)
     */
    public function tenureSteps(Request $request): View
    {
        $data = $this->tenureProgressionService->getTenureStepOverview();
        $salaryGrades = $data['salary_grades'];
        $candidates = $data['candidates'];

        return view('payroll-benefits.compensation.tenure-steps', compact(
            'salaryGrades',
            'candidates'
        ));
    }

    /**
     * Store or Update a Salary Step for a Grade
     */
    public function storeSalaryStep(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'salary_grade_id' => 'required|exists:salary_grades,id',
            'step_number' => 'required|integer|min:1|max:10',
            'years_required' => 'required|numeric|min:0',
            'increment_percentage' => 'required|numeric|min:0',
            'base_amount' => 'nullable|numeric|min:0',
        ]);

        SalaryStep::updateOrCreate(
            [
                'salary_grade_id' => $validated['salary_grade_id'],
                'step_number' => $validated['step_number'],
            ],
            [
                'years_required' => $validated['years_required'],
                'increment_percentage' => $validated['increment_percentage'],
                'base_amount' => $validated['base_amount'] ?? null,
            ]
        );

        return redirect()->back()->with('status', "Tenure step {$validated['step_number']} successfully saved for selected salary grade.");
    }

    /**
     * Apply a Tenure Step Increment to an Employee
     */
    public function applyStep(Employee $employee, Request $request): RedirectResponse
    {
        $targetStep = $request->input('target_step') !== null ? (int) $request->input('target_step') : null;
        $newRate = $request->input('new_rate') !== null ? (float) $request->input('new_rate') : null;
        $reason = $request->input('reason', 'Tenure step increment applied.');

        $success = $this->tenureProgressionService->applyStepAdvance($employee, $targetStep, $newRate, $reason);

        if (! $success) {
            return redirect()->back()->with('error', "Employee {$employee->first_name} {$employee->last_name} is already at the maximum step (Step 7).");
        }

        return redirect()->back()->with('status', "Successfully applied Step increment for {$employee->first_name} {$employee->last_name}!");
    }

    /**
     * Hold a Tenure Step Increment
     */
    public function holdStep(Employee $employee, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hold_reason' => 'required|string|max:500',
        ]);

        $this->tenureProgressionService->holdStepAdvance($employee, $validated['hold_reason']);

        return redirect()->back()->with('status', "Tenure step increment placed ON HOLD for {$employee->first_name} {$employee->last_name}.");
    }

    /**
     * Audit Trail & Compliance Log Sub-Module (Section 2.16 & Prof Notes #8, #9)
     */
    public function auditTrail(Request $request): View
    {
        $search = $request->query('search');
        $action = $request->query('action');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = PayrollAuditTrail::latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%");
            });
        }

        if ($action && $action !== 'all') {
            $query->where('action', $action);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $auditLogs = $query->paginate(15)->withQueryString();
        $distinctActions = PayrollAuditTrail::select('action')->distinct()->pluck('action');

        return view('payroll-benefits.compensation.audit-trail', compact(
            'auditLogs',
            'distinctActions',
            'search',
            'action',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export Audit Trail to CSV (Section 2.16)
     */
    public function exportAuditTrail(Request $request): StreamedResponse
    {
        return $this->auditTrailExportService->streamAuditTrailCsv($request->all());
    }

    /**
     * Validate Financial Budget Requisition with Team 5 API (§10.1, §13)
     */
    public function validateFinanceBudget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'adjustment_id' => 'required|exists:compensation_adjustments,id',
        ]);

        $adjustment = CompensationAdjustment::with(['employee.department'])->findOrFail($validated['adjustment_id']);
        $result = $this->compensationApprovalService->submitForFinanceValidation($adjustment);

        return response()->json($result);
    }

    /**
     * Grant Team 8 Administrative & Executive Approval (§6.8, §13)
     */
    public function adminApproveAdjustment(CompensationAdjustment $adjustment, Request $request): RedirectResponse
    {
        $adminName = $request->input('admin_name', 'Corporate Admin');
        $adminNotes = $request->input('admin_notes');

        $approved = $this->compensationApprovalService->grantAdminApproval($adjustment, $adminName, $adminNotes);

        if (! $approved) {
            return redirect()->back()->with('error', 'Cannot grant Admin Approval: Team 5 Financial Budget validation has not passed or was rejected.');
        }

        return redirect()->back()->with('status', 'Team 8 Administrative Approval successfully granted!');
    }

    /**
     * Finalize & Approve a Compensation Adjustment (Real-Time Active Payroll Sync)
     */
    public function approveAdjustment(CompensationAdjustment $adjustment): RedirectResponse
    {
        $success = $this->compensationApprovalService->finalizeAndSyncToPayroll($adjustment);

        if (! $success) {
            return redirect()->back()->with('error', 'Cannot finalize approval: Proposal requires approved Financial Budget and Administrative clearance.');
        }

        return redirect()->back()->with('status', 'Compensation adjustment APPROVED and synchronized in real-time with employee payroll ledger!');
    }

    /**
     * Reject a Compensation Adjustment
     */
    public function rejectAdjustment(CompensationAdjustment $adjustment, Request $request): RedirectResponse
    {
        $reason = $request->input('reason', 'Administrative decision.');
        $rejector = $request->input('rejected_by', 'Corporate Admin');

        $this->compensationApprovalService->rejectAdjustment($adjustment, $reason, $rejector);

        return redirect()->back()->with('status', 'Compensation adjustment rejected.');
    }
}
