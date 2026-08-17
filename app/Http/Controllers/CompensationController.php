<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryGrade;
use App\Models\SalaryStep;
use App\Services\Compensation\AuditTrailExportService;
use App\Services\Compensation\BonusPoolDistributionService;
use App\Services\Compensation\CompensationApprovalService;
use App\Services\Compensation\CounterOfferService;
use App\Services\Compensation\MeritIncreaseService;
use App\Services\Compensation\ProbationaryConversionService;
use App\Services\Compensation\RetroactivePayCalculationService;
use App\Services\Compensation\SalaryDeterminationService;
use App\Services\Compensation\TenureProgressionService;
use App\Services\CompensationService;
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
        protected CompensationService $compensationService,
        protected FinancialService $financialService,
        protected CounterOfferService $counterOfferService,
        protected SalaryDeterminationService $salaryDeterminationService,
        protected MeritIncreaseService $meritIncreaseService,
        protected RetroactivePayCalculationService $retroactivePayCalculationService,
        protected TenureProgressionService $tenureProgressionService,
        protected ProbationaryConversionService $probationaryConversionService,
        protected BonusPoolDistributionService $bonusPoolDistributionService,
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

        return view('payroll-benefits.compensation.salary-bands', compact(
            'employees',
            'departments',
            'salaryGrades',
            'bandHistory',
            'search',
            'deptId'
        ));
    }

    /**
     * Determine Recommended Starting Salary based on 5-Factor Weighted Candidate Scoring (known.md §6.3)
     */
    public function determineSalary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'salary_grade_id' => 'required|exists:salary_grades,id',
            'education' => 'required|integer|min:1|max:6',
            'experience' => 'required|integer|min:1|max:6',
            'skills' => 'required|integer|min:1|max:6',
            'market_benchmark' => 'required|integer|min:1|max:6',
            'internal_equity' => 'required|integer|min:1|max:6',
        ]);

        $grade = SalaryGrade::findOrFail($validated['salary_grade_id']);
        $result = $this->salaryDeterminationService->calculateRecommendedSalary($grade, $validated);

        return response()->json($result);
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

        $this->compensationService->updateSalaryBand(
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

        $result = $this->compensationService->bulkAdjustBands((float) $validated['percentage']);

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
            'inject_to_cutoff_id' => 'nullable|exists:payroll_cutoffs,id',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $result = $this->retroactivePayCalculationService->calculateRetroactiveDifferential(
            $employee,
            (float) $validated['new_monthly_rate'],
            $validated['effective_date'],
            (int) $validated['days_worked']
        );

        if (! empty($validated['inject_to_cutoff_id'])) {
            $injected = $this->retroactivePayCalculationService->injectRetroactivePayToPayroll(
                $employee,
                (float) $result['retroactive_pay'],
                (int) $validated['inject_to_cutoff_id']
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

        return view('payroll-benefits.compensation.merit-promotions', compact(
            'adjustments',
            'employees',
            'departments',
            'salaryGrades',
            'search',
            'deptId'
        ));
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
                    $raisePct = (float) ($plan['raise_pct'] ?? 0.0);
                    $rating = (string) ($plan['rating'] ?? $emp->performance_rating ?? 'Satisfactory');
                    $newSalary = isset($plan['new_salary']) && (float) $plan['new_salary'] > 0
                        ? (float) $plan['new_salary']
                        : round($oldSalary * (1 + ($raisePct / 100)), 2);

                    if ($newSalary <= 0 || ($newSalary <= $oldSalary && $raisePct <= 0)) {
                        continue;
                    }

                    $isDriver = str_contains(strtolower($emp->position ?? ''), 'driver');
                    $dailyRate = round($newSalary / $workingDays, 2);

                    $empUpdates = [
                        'monthly_rate' => $newSalary,
                    ];

                    if ($isDriver) {
                        $empUpdates['daily_rate'] = $dailyRate;
                    }

                    $emp->update($empUpdates);

                    $monthlyCtc = (float) ($plan['monthly_ctc'] ?? round($newSalary * 1.135, 2));
                    $annualCtc = (float) ($plan['annual_ctc'] ?? round($monthlyCtc * 12, 2));

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
                        'reason' => "5-Tier Merit Increase of {$raisePct}% based on Team 3 rating: {$rating}",
                        'effective_date' => now(),
                    ]);

                    PayrollAuditTrail::create([
                        'action' => 'MERIT_PROMOTION_BATCH_COMMITTED',
                        'model_type' => Employee::class,
                        'model_id' => $emp->id,
                        'user_name' => 'HR Manager',
                        'ip_address' => request()->ip() ?? '127.0.0.1',
                        'old_values' => ['monthly_rate' => $oldSalary],
                        'new_values' => ['monthly_rate' => $newSalary, 'raise_pct' => $raisePct],
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
                $adjustment->update([
                    'status' => 'approved',
                    'budget_impact_status' => 'BUDGET_APPROVED',
                    'admin_approval_status' => 'ADMIN_APPROVED',
                ]);

                if ($adjustment->employee && $adjustment->new_rate > 0) {
                    $isDriver = str_contains(strtolower($adjustment->employee->position ?? ''), 'driver');
                    $empUpdates = [
                        'monthly_rate' => $adjustment->new_rate,
                    ];
                    if ($isDriver) {
                        $empUpdates['daily_rate'] = round($adjustment->new_rate / 26, 2);
                    }
                    $adjustment->employee->update($empUpdates);

                    PayrollAuditTrail::create([
                        'action' => 'MERIT_PROMOTION_APPROVED',
                        'model_type' => Employee::class,
                        'model_id' => $adjustment->employee->id,
                        'user_name' => 'HR Manager',
                        'ip_address' => request()->ip() ?? '127.0.0.1',
                        'old_values' => ['monthly_rate' => $adjustment->old_rate],
                        'new_values' => ['monthly_rate' => $adjustment->new_rate],
                    ]);

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

    /**
     * Calculate 6-Month Probationary Status & Regularization Proposal API (known.md §6.8)
     */
    public function calculateProbationaryConversion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $employee = Employee::with('department')->findOrFail($validated['employee_id']);
        $result = $this->probationaryConversionService->evaluateProbationaryStatus($employee);

        return response()->json($result);
    }

    /**
     * Calculate Weighted Bonus Pool Proportional Distribution API (known.md §6.10 & Section 2.11)
     */
    public function calculateBonusDistribution(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pool_amount' => 'required|numeric|min:1',
            'department_id' => 'nullable|integer',
            'bonus_type' => 'required|string|in:performance,mid_year,fourteenth_month,tenure_milestone',
        ]);

        $deptId = ! empty($validated['department_id']) && $validated['department_id'] > 0 ? (int) $validated['department_id'] : null;

        $result = $this->bonusPoolDistributionService->calculateDistribution(
            (float) $validated['pool_amount'],
            $deptId,
            $validated['bonus_type']
        );

        return response()->json($result);
    }

    /**
     * Bonus Allocation Sub-Module (Section 2.11 & Prof Note #4)
     */
    public function bonusAllocation(Request $request): View
    {
        $deptId = $request->query('department');

        $query = Employee::with('department')->where('employment_status', '!=', 'resigned');

        if ($deptId && $deptId !== 'all') {
            $query->where('department_id', $deptId);
        }

        $employees = $query->orderBy('first_name')->get();
        $departments = Department::all();

        return view('payroll-benefits.compensation.bonus-allocation', compact(
            'employees',
            'departments',
            'deptId'
        ));
    }

    /**
     * Distribute and Store Bonus Pool to Employees (Section 2.11)
     */
    public function storeBonusAllocation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bonus_type' => 'required|string|max:100',
            'pool_amount' => 'required|numeric|min:1',
            'department_id' => 'nullable|integer',
            'allocations' => 'nullable|array',
        ]);

        $deptId = ! empty($validated['department_id']) && $validated['department_id'] > 0 ? (int) $validated['department_id'] : null;

        $this->bonusPoolDistributionService->commitBonusAllocation(
            (float) $validated['pool_amount'],
            $deptId,
            $validated['bonus_type'],
            $validated['allocations'] ?? []
        );

        return redirect()->back()->with('status', "Successfully distributed PHP ".number_format((float)$validated['pool_amount'], 2)." ({$validated['bonus_type']}) across eligible personnel. Synced to Payroll pick-up.");
    }

    /**
     * Tenure Step Grid & Management (Section 2.12 & Prof Note #6)
     */
    public function tenureSteps(Request $request): View
    {
        $data = $this->compensationService->getTenureStepOverview();
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
     * Probationary to Regular Conversion Review Sub-Module (Section 2.8 & Prof Note #9)
     */
    public function probationary(Request $request): View
    {
        $overview = $this->compensationService->getProbationaryOverview();
        $probationaryEmployees = $overview['employees'] ?? [];

        return view('payroll-benefits.compensation.probationary', compact('overview', 'probationaryEmployees'));
    }

    /**
     * Regularize an Employee from Probationary Status
     */
    public function regularize(Employee $employee, Request $request): RedirectResponse
    {
        $decision = $request->input('decision', 'regularize');

        if ($decision === 'extend') {
            $validated = $request->validate([
                'extension_months' => 'required|integer|min:1|max:6',
                'reason' => 'required|string|max:500',
            ]);

            $this->probationaryConversionService->extendProbation($employee, (int) $validated['extension_months'], $validated['reason']);

            return redirect()->back()->with('status', "Probationary period for {$employee->first_name} {$employee->last_name} extended by {$validated['extension_months']} months.");
        }

        if ($decision === 'terminate') {
            $validated = $request->validate([
                'termination_reason' => 'required|string|max:500',
            ]);

            $employee->update(['employment_status' => 'resigned']);

            PayrollAuditTrail::create([
                'user_name' => 'HR Operations',
                'action' => 'PROBATION_TERMINATED',
                'model_type' => Employee::class,
                'model_id' => $employee->id,
                'new_values' => ['reason' => $validated['termination_reason']],
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);

            return redirect()->back()->with('status', "Probation concluded. Separation and offboarding workflow triggered for {$employee->first_name} {$employee->last_name}.");
        }

        // Default: Regularize
        $validated = $request->validate([
            'new_rate' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $this->probationaryConversionService->regularizeEmployee(
            $employee,
            (float) $validated['new_rate'],
            $validated['reason'] ?? null
        );

        return redirect()->back()->with('status', "Employee {$employee->first_name} {$employee->last_name} has been officially REGULARIZED with updated compensation!");
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
     * Ajax API: Simulate Compensation & Budget Check for UI Modal On-the-fly
     */
    public function simulateCompensation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position' => 'required|string',
            'years_experience' => 'nullable|integer|min:0',
            'certifications_count' => 'nullable|integer|min:0',
            'education_level' => 'nullable|string',
            'performance_rating' => 'nullable|string',
            'proposed_salary' => 'nullable|numeric|min:0',
            'competitor_offer' => 'nullable|numeric|min:0',
            'current_salary' => 'nullable|numeric|min:0',
        ]);

        $result = $this->compensationService->computeCounterOffer(
            $validated['position'],
            (int) ($validated['years_experience'] ?? 0),
            (int) ($validated['certifications_count'] ?? 0),
            (float) ($validated['competitor_offer'] ?? 0.0),
            (float) ($validated['current_salary'] ?? 0.0),
            $validated['education_level'] ?? 'College Graduate',
            $validated['performance_rating'] ?? 'Satisfactory'
        );

        if (! empty($validated['proposed_salary']) && (float) $validated['proposed_salary'] > 0) {
            $salary = (float) $validated['proposed_salary'];
            $budgetCheck = $this->financialService->checkBudgetAvailability($salary, 'Human Resources');
            $result['computed_counter_offer'] = $salary;
            $result['financial_budget_check'] = $budgetCheck;
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
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
