<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ClaimBatchWorkflowRequest;
use App\Http\Requests\ClaimCategoryRequest;
use App\Http\Requests\ClaimPolicySettingsRequest;
use App\Http\Requests\ClaimWorkflowActionRequest;
use App\Http\Requests\DriverMilestoneIncentiveRequest;
use App\Http\Requests\FuelReimbursementRequest;
use App\Http\Requests\MaternityBenefitClaimRequest;
use App\Http\Requests\MedicalAssistanceClaimRequest;
use App\Http\Requests\OperationalExpenseRequest;
use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\TripIncome;
use App\Services\Claims\ClaimCategoryManagementService;
use App\Services\Claims\ClaimGovernanceWorkflowService;
use App\Services\Claims\ClaimTaxabilityService;
use App\Services\Claims\ClaimsPayrollSyncService;
use App\Services\Claims\DriverMilestoneIncentiveService;
use App\Services\Claims\DuplicateClaimDetectionService;
use App\Services\Claims\FuelReimbursementValidationService;
use App\Services\Claims\MaternityBenefitService;
use App\Services\Claims\MedicalAssistanceService;
use App\Services\Claims\OperationalExpenseService;
use App\Services\PayrollEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function __construct(
        protected ClaimCategoryManagementService $categoryService,
        protected ClaimTaxabilityService $taxabilityService,
        protected DriverMilestoneIncentiveService $milestoneService,
        protected FuelReimbursementValidationService $fuelService,
        protected OperationalExpenseService $expenseService,
        protected MaternityBenefitService $maternityService,
        protected MedicalAssistanceService $medicalService,
        protected ClaimGovernanceWorkflowService $governanceService,
        protected DuplicateClaimDetectionService $duplicateService,
        protected ClaimsPayrollSyncService $payrollSyncService
    ) {}

    /**
     * Helper: Compute summary stats for any claim type
     */
    private function getStatsForType(string $type): array
    {
        $all = Claim::where('type', $type)->get();
        $pending = $all->whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending']);

        return [
            'pending_count' => $pending->count(),
            'overdue_count' => $pending->filter(fn (Claim $c) => $c->isOverdue())->count(),
            'approved_count' => $all->whereIn('approval_status', ['approved', 'payroll_queued', 'paid'])->count(),
            'total_disbursed' => (float) $all->whereIn('approval_status', ['approved', 'payroll_queued', 'paid'])->sum('amount'),
            'total_claims_count' => $all->count(),
        ];
    }

    /**
     * 3.4 Driver & Staff Expense Reimbursements (Fuel & Operational Expenses - known.md §7.5, §7.6)
     */
    public function expenses(Request $request): View
    {
        $search = $request->query('search');
        $subtype = $request->query('subtype');
        $status = $request->query('status');
        $aging = $request->query('aging');

        $query = Claim::with(['employee.department', 'categoryModel'])
            ->where('type', 'expense')
            ->latest();

        if ($aging === 'overdue') {
            $query->whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending'])
                ->where('created_at', '<=', now()->subDays(3));
        }

        if ($subtype) {
            $query->where('expense_subtype', $subtype);
        }

        if ($status) {
            if ($status === 'needs_action') {
                $query->whereIn('approval_status', ['pending_hr', 'pending', 'pending_finance', 'pending_admin']);
            } elseif ($status === 'ready_payroll') {
                $query->whereIn('approval_status', ['approved', 'payroll_queued']);
            } elseif ($status === 'auto_verified') {
                $query->where('auto_validated', true);
            } elseif ($status === 'flagged_variance') {
                $query->where('validation_status', 'flagged_variance');
            } else {
                $query->where('approval_status', $status);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('merchant_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->search($search);
                    });
            });
        }

        $claims = $query->paginate(10)->withQueryString();
        $stats = $this->expenseService->getExpenseSummaryStats();
        $fuelTolerancePct = $this->fuelService->getTolerancePercentage();

        $filterCounts = [
            'all' => Claim::where('type', 'expense')->count(),
            'needs_action' => Claim::where('type', 'expense')->whereIn('approval_status', ['pending_hr', 'pending', 'pending_finance', 'pending_admin'])->count(),
            'overdue' => Claim::where('type', 'expense')->whereIn('approval_status', ['pending_hr', 'pending', 'pending_finance', 'pending_admin'])->where('created_at', '<=', now()->subDays(3))->count(),
            'ready_payroll' => Claim::where('type', 'expense')->whereIn('approval_status', ['approved', 'payroll_queued'])->count(),
        ];

        return view('payroll-benefits.claims.expenses', compact('claims', 'stats', 'search', 'subtype', 'status', 'aging', 'fuelTolerancePct', 'filterCounts'));
    }

    /**
     * Store a Validated Fuel Reimbursement Claim
     */
    public function storeFuelClaim(FuelReimbursementRequest $request): RedirectResponse
    {
        $claim = $this->fuelService->fileFuelClaim($request->validated(), $request->file('receipt_file'));
        $statusMsg = $claim->auto_validated
            ? "Fuel claim [{$claim->receipt_number}] for PHP " . number_format((float) $claim->amount, 2) . " auto-verified within 15% tolerance."
            : "Fuel claim [{$claim->receipt_number}] filed with flagged variance (+{$claim->fuel_variance_pct}%). Sent for HR review.";

        return redirect()->back()->with('status', $statusMsg);
    }

    /**
     * Store an Operational Expense Claim (Toll, Maintenance, Parking, Meal, etc.)
     */
    public function storeOperationalExpense(OperationalExpenseRequest $request): RedirectResponse
    {
        $claim = $this->expenseService->fileOperationalClaim($request->validated(), $request->file('receipt_file'));

        return redirect()->back()->with('status', "Operational expense claim [{$claim->receipt_number}] for PHP " . number_format((float) $claim->amount, 2) . " submitted successfully.");
    }


    /**
     * 3.3 Driver Ride-Based Incentive (TNVS Milestone System - known.md §7.4)
     */
    public function incentives(Request $request): View
    {
        $search = $request->query('search');
        $currentCutoff = $request->query('cutoff', '2026-07-01_15');
        $aging = $request->query('aging');

        $query = Claim::with(['employee.department', 'categoryModel'])
            ->where('type', 'incentive')
            ->latest();

        if ($aging === 'overdue') {
            $query->whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending'])
                ->where('created_at', '<=', now()->subDays(3));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->search($search);
                    });
            });
        }

        $claims = $query->paginate(10)->withQueryString();
        $driverRoster = $this->milestoneService->qualifyDriverRoster($currentCutoff);
        $tiers = $this->milestoneService->getTiers();

        $stats = $this->getStatsForType('incentive');
        $stats['qualified_drivers_count'] = $driverRoster->where('is_qualified', true)->count();
        $stats['total_projected_incentives'] = (float) $driverRoster->sum('total_incentive_amount');

        return view('payroll-benefits.claims.incentives', compact('claims', 'driverRoster', 'tiers', 'stats', 'search', 'currentCutoff', 'aging'));
    }

    /**
     * Batch Qualify and Commit Driver Milestone Incentives into Active Claims
     */
    public function batchQualifyIncentives(DriverMilestoneIncentiveRequest $request): RedirectResponse
    {
        $cutoff = $request->input('cutoff_period', '2026-07-01_15');
        $plansJson = $request->input('plans_json');
        $plans = [];

        if (is_string($plansJson)) {
            $decoded = json_decode($plansJson, true);
            if (is_array($decoded)) {
                $plans = $decoded;
            }
        }

        if (empty($plans)) {
            $roster = $this->milestoneService->qualifyDriverRoster($cutoff);
            $plans = $roster->where('is_qualified', true)->toArray();
        }

        $committedCount = $this->milestoneService->batchCommitDriverIncentives($plans, $cutoff);

        return redirect()->back()->with('status', "Successfully committed {$committedCount} driver milestone incentives for cutoff period [{$cutoff}].");
    }

    /**
     * 3.6 Statutory Maternity Benefit Engine (RA 11210 - known.md §7.8)
     */
    public function maternityLeave(Request $request): View
    {
        $search = $request->query('search');
        $sssStatus = $request->query('sss_status');
        $aging = $request->query('aging');

        $query = Claim::with(['employee.department', 'categoryModel'])
            ->where('type', 'maternity')
            ->latest();

        if ($aging === 'overdue') {
            $query->whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending'])
                ->where('created_at', '<=', now()->subDays(3));
        }

        if ($sssStatus) {
            $query->where('sss_reimbursement_status', $sssStatus);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->search($search);
                    });
            });
        }

        $claims = $query->paginate(10)->withQueryString();
        $stats = $this->maternityService->getMaternitySummaryStats();

        return view('payroll-benefits.claims.maternity-leave', compact('claims', 'stats', 'search', 'sssStatus', 'aging'));
    }

    /**
     * Store a new RA 11210 Maternity Benefit Advance Claim
     */
    public function storeMaternityClaim(MaternityBenefitClaimRequest $request): RedirectResponse
    {
        $claim = $this->maternityService->fileMaternityClaim($request->validated(), $request->file('receipt_file'));

        return redirect()->back()->with('status', "Maternity benefit claim [{$claim->receipt_number}] for PHP " . number_format((float) $claim->amount, 2) . " filed successfully (SSS Advance: PHP " . number_format((float) $claim->sss_maternity_share, 2) . ", Company Differential: PHP " . number_format((float) $claim->company_maternity_topup, 2) . ").");
    }

    /**
     * Update Employer SSS Reimbursement Recovery Lifecycle Status
     */
    public function updateSssStatus(Request $request, Claim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'sss_reimbursement_status' => ['required', 'string', 'in:pending_advance,advanced_to_employee,submitted_to_sss,reimbursed_by_sss'],
            'sss_reference_number' => ['nullable', 'string', 'max:100'],
            'sss_reimbursement_date' => ['nullable', 'date'],
        ]);

        $this->maternityService->updateSssReimbursementStatus(
            $claim,
            $validated['sss_reimbursement_status'],
            $validated['sss_reference_number'] ?? null,
            $validated['sss_reimbursement_date'] ?? null
        );

        return redirect()->back()->with('status', "SSS Reimbursement status for [{$claim->receipt_number}] updated to '{$validated['sss_reimbursement_status']}'.");
    }

    /**
     * Store an Internal Employee Medical Assistance Claim (PHP 10k de minimis cap)
     */
    public function storeMedicalClaim(MedicalAssistanceClaimRequest $request): RedirectResponse
    {
        $claim = $this->medicalService->fileMedicalClaim($request->validated(), $request->file('receipt_file'));

        return redirect()->back()->with('status', "Medical assistance claim [{$claim->receipt_number}] for PHP " . number_format((float) $claim->amount, 2) . " submitted (Non-Taxable: PHP " . number_format((float) $claim->non_taxable_amount, 2) . ", Taxable: PHP " . number_format((float) $claim->taxable_amount, 2) . ").");
    }


    /**
     * 3.1 Claim Category Setup & Management
     */
    public function categories(): View
    {
        $categories = ClaimCategory::withCount('claims')->orderBy('type')->orderBy('name')->get();
        $stats = $this->categoryService->getSummaryStats();

        $policySettings = [
            'fuel_default_pump_price' => $this->fuelService->getDefaultPumpPrice(),
            'fuel_default_efficiency_kpl' => $this->fuelService->getDefaultEfficiency(),
            'fuel_tolerance_percentage' => $this->fuelService->getTolerancePercentage(),
            'performance_bonus_multiplier' => (float) CompanySetting::getValue('performance_bonus_multiplier', 1500.00),
            'driver_consistency_bonus' => $this->milestoneService->getConsistencyBonus(),
            'driver_attendance_bonus' => $this->milestoneService->getAttendanceBonus(),
            'sss_max_msc' => $this->maternityService->getSssMaxMsc(),
            'medical_de_minimis_annual_cap' => $this->taxabilityService->getMedicalDeMinimisCap(),
            'milestone_tiers' => $this->milestoneService->getTiers(),
        ];

        return view('payroll-benefits.claims.categories', compact('categories', 'stats', 'policySettings'));
    }

    /**
     * Update Company Claim Policies, Rates, and Milestone Tiers
     */
    public function updateSettings(ClaimPolicySettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        CompanySetting::setValue('fuel_default_pump_price', $validated['fuel_default_pump_price'], 'Default fuel pump price in PHP/L');
        CompanySetting::setValue('fuel_default_efficiency_kpl', $validated['fuel_default_efficiency_kpl'], 'Default vehicle fuel efficiency in km/L');
        CompanySetting::setValue('fuel_tolerance_percentage', $validated['fuel_tolerance_percentage'], 'Allowable fuel variance tolerance percentage');
        CompanySetting::setValue('performance_bonus_multiplier', $validated['performance_bonus_multiplier'], 'Base monetary bonus per rating score point');
        CompanySetting::setValue('driver_consistency_bonus', $validated['driver_consistency_bonus'], 'Monthly consistency bonus for drivers');
        CompanySetting::setValue('driver_attendance_bonus', $validated['driver_attendance_bonus'], 'Perfect attendance bonus for drivers');
        CompanySetting::setValue('sss_max_msc', $validated['sss_max_msc'], 'Statutory SSS Monthly Salary Credit ceiling');
        CompanySetting::setValue('medical_de_minimis_annual_cap', $validated['medical_de_minimis_annual_cap'], 'Annual non-taxable medical assistance de minimis cap');

        if (! empty($validated['milestone_tiers'])) {
            CompanySetting::setValue('driver_milestone_tiers', json_encode(array_values($validated['milestone_tiers'])), 'Configured TNVS driver ride milestone tiers');
        }

        return redirect()->back()->with('status', 'Company claim rates, driver milestone tiers, and statutory ceilings updated successfully.');
    }

    /**
     * Reset Company Claim Policies, Rates, and Milestone Tiers to Factory Defaults
     */
    public function resetPolicySettings(): RedirectResponse
    {
        CompanySetting::setValue('fuel_default_pump_price', 65.00, 'Default fuel pump price in PHP/L');
        CompanySetting::setValue('fuel_default_efficiency_kpl', 10.00, 'Default vehicle fuel efficiency in km/L');
        CompanySetting::setValue('fuel_tolerance_percentage', 15.00, 'Allowable fuel variance tolerance percentage');
        CompanySetting::setValue('performance_bonus_multiplier', 1500.00, 'Base monetary bonus per rating score point');
        CompanySetting::setValue('driver_consistency_bonus', 500.00, 'Monthly consistency bonus for drivers');
        CompanySetting::setValue('driver_attendance_bonus', 500.00, 'Perfect attendance bonus for drivers');
        CompanySetting::setValue('sss_max_msc', 35000.00, 'Statutory SSS Monthly Salary Credit ceiling');
        CompanySetting::setValue('medical_de_minimis_annual_cap', 10000.00, 'Annual non-taxable medical assistance de minimis cap');

        $defaultTiers = [
            ['tier' => 1, 'label' => 'Tier 1 (Base)', 'min_rides' => 10, 'amount' => 250.00],
            ['tier' => 2, 'label' => 'Tier 2 (Standard)', 'min_rides' => 20, 'amount' => 500.00],
            ['tier' => 3, 'label' => 'Tier 3 (High Performer)', 'min_rides' => 30, 'amount' => 1000.00],
            ['tier' => 4, 'label' => 'Tier 4 (Star Driver)', 'min_rides' => 50, 'amount' => 2000.00],
        ];
        CompanySetting::setValue('driver_milestone_tiers', json_encode($defaultTiers), 'Configured TNVS driver ride milestone tiers');

        PayrollAuditTrail::create([
            'action' => 'CLAIM_POLICY_SETTINGS_RESET',
            'model_type' => CompanySetting::class,
            'user_name' => auth()->user()?->name ?? 'System Admin',
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'old_values' => [],
            'new_values' => ['status' => 'Reset to company factory defaults'],
        ]);

        return redirect()->back()->with('status', 'Company claim rates, fuel parameters, and milestone tiers have been successfully reset to standard company defaults.');
    }

    /**
     * Store new Category with Form Request validation
     */
    public function storeCategory(ClaimCategoryRequest $request): RedirectResponse
    {
        $category = $this->categoryService->createCategory($request->validated());

        return redirect()->back()->with('status', "Category [{$category->code}] {$category->name} created successfully with tax classification '{$category->getTaxLabel()}'.");
    }

    /**
     * Toggle Category Active State
     */
    public function toggleCategory(ClaimCategory $category): RedirectResponse
    {
        $newStatus = $this->categoryService->toggleCategoryStatus($category);
        $statusText = $newStatus ? 'activated' : 'deactivated';

        return redirect()->back()->with('status', "Category '{$category->name}' has been {$statusText}.");
    }

    /**
     * Update Category Details
     */
    public function updateCategory(ClaimCategoryRequest $request, ClaimCategory $category): RedirectResponse
    {
        $this->categoryService->updateCategory($category, $request->validated());

        return redirect()->back()->with('status', "Category '{$category->name}' updated successfully.");
    }

    /**
     * Unified Single Claim Workflow Action Handler
     */
    public function workflowAction(ClaimWorkflowActionRequest $request, Claim $claim): RedirectResponse
    {
        $action = $request->input('action');
        $approvedAmount = $request->filled('approved_amount') ? (float) $request->input('approved_amount') : null;
        $remarks = $request->input('remarks', '');
        $rejectionReason = $request->input('rejection_reason', '');
        $role = $request->input('role', 'HR Reviewer');

        $updated = match ($action) {
            'approve_supervisor' => $this->governanceService->approveSupervisor($claim, $approvedAmount, $remarks),
            'approve_hr' => $this->governanceService->approveHR($claim, $approvedAmount, $remarks),
            'approve_finance' => $this->governanceService->approveFinance($claim, $approvedAmount, $remarks),
            'approve_admin' => $this->governanceService->approveAdmin($claim, $approvedAmount, $remarks),
            'queue_payroll' => $this->governanceService->queueToPayroll($claim),
            'mark_paid' => $this->governanceService->markPaid($claim),
            'reject' => $this->governanceService->rejectClaim($claim, $rejectionReason, $role),
            default => $claim,
        };

        $msg = match ($action) {
            'approve_supervisor' => "Claim [{$claim->receipt_number}] approved by Immediate Supervisor.",
            'approve_hr' => "Claim [{$claim->receipt_number}] validated by HR and routed to Finance.",
            'approve_finance' => "Claim [{$claim->receipt_number}] approved by Finance and routed to Admin.",
            'approve_admin' => "Claim [{$claim->receipt_number}] fully authorized by Admin.",
            'queue_payroll' => "Claim [{$claim->receipt_number}] queued into Active Payroll.",
            'mark_paid' => "Claim [{$claim->receipt_number}] marked as Paid / Disbursed.",
            'reject' => "Claim [{$claim->receipt_number}] rejected with documented remarks.",
            default => "Claim [{$claim->receipt_number}] updated.",
        };

        return redirect()->back()->with('status', $msg);
    }

    /**
     * Unified Batch Workflow Action Handler
     */
    public function batchWorkflow(ClaimBatchWorkflowRequest $request): RedirectResponse
    {
        $ids = (array) $request->input('selected_ids', []);
        $action = (string) $request->input('action');
        $role = (string) $request->input('role', 'System Reviewer');
        $remarks = $request->input('remarks');

        $count = $this->governanceService->batchProcessClaims($ids, $action, $role, $remarks);

        return redirect()->back()->with('status', "Batch action '{$action}' successfully executed on {$count} claim(s).");
    }

    /**
     * Trigger Real-Time Active Payroll Synchronization
     */
    public function syncPayroll(Request $request): RedirectResponse
    {
        $cutoffPeriod = $request->input('cutoff_period', '2026-07-01_15');
        $result = $this->payrollSyncService->syncApprovedClaimsToPayroll($cutoffPeriod);

        return redirect()->back()->with('status', "Active Payroll Sync completed for [{$cutoffPeriod}]: {$result['synced_claims_count']} claims synced (PHP " . number_format($result['total_non_taxable_reimbursements'], 2) . " Non-Taxable Reimbursements).");
    }


    /**
     * 3.10 Consolidated Claims & Incentives Summary Report Dashboard
     */
    public function reports(Request $request): View
    {
        $type = $request->query('type');
        $departmentId = $request->query('department_id');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Claim::with(['employee.department', 'categoryModel'])->latest();

        if ($type) {
            $query->where('type', $type);
        }
        if ($departmentId) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId));
        }
        if ($status) {
            $query->where('approval_status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $allClaims = Claim::with('employee.department')->get();
        $approvedClaims = $allClaims->whereIn('approval_status', ['approved', 'payroll_queued', 'paid']);
        $pendingClaims = $allClaims->whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending']);
        $rejectedClaims = $allClaims->where('approval_status', 'rejected');

        // High-level KPI metrics
        $stats = [
            'total_claims_count' => $allClaims->count(),
            'total_disbursed' => (float) $approvedClaims->sum('amount'),
            'non_taxable_total' => (float) $approvedClaims->sum('non_taxable_amount'),
            'taxable_total' => (float) $approvedClaims->sum('taxable_amount'),
            'pending_count' => $pendingClaims->count(),
            'pending_amount' => (float) $pendingClaims->sum('amount'),
            'overdue_count' => $pendingClaims->filter(fn (Claim $c) => $c->isOverdue())->count(),
            'rejected_count' => $rejectedClaims->count(),
            'rejected_amount' => (float) $rejectedClaims->sum('amount'),
            'sla_on_time_rate' => $pendingClaims->count() > 0
                ? round((($pendingClaims->count() - $pendingClaims->filter(fn (Claim $c) => $c->isOverdue())->count()) / $pendingClaims->count()) * 100, 1)
                : 100.0,
        ];

        // 4-Type Financial Breakdown
        $typeBreakdowns = [
            'expense' => [
                'label' => 'Driver & Operational Expenses',
                'count' => $allClaims->where('type', 'expense')->count(),
                'approved_count' => $approvedClaims->where('type', 'expense')->count(),
                'disbursed_amount' => (float) $approvedClaims->where('type', 'expense')->sum('amount'),
                'pending_count' => $pendingClaims->where('type', 'expense')->count(),
            ],
            'incentive' => [
                'label' => 'Driver Ride Milestone Rewards',
                'count' => $allClaims->where('type', 'incentive')->count(),
                'approved_count' => $approvedClaims->where('type', 'incentive')->count(),
                'disbursed_amount' => (float) $approvedClaims->where('type', 'incentive')->sum('amount'),
                'pending_count' => $pendingClaims->where('type', 'incentive')->count(),
            ],
            'performance' => [
                'label' => 'Employee Performance Bonuses',
                'count' => $allClaims->where('type', 'performance')->count(),
                'approved_count' => $approvedClaims->where('type', 'performance')->count(),
                'disbursed_amount' => (float) $approvedClaims->where('type', 'performance')->sum('amount'),
                'pending_count' => $pendingClaims->where('type', 'performance')->count(),
            ],
            'maternity' => [
                'label' => 'Statutory Maternity Advances',
                'count' => $allClaims->where('type', 'maternity')->count(),
                'approved_count' => $approvedClaims->where('type', 'maternity')->count(),
                'disbursed_amount' => (float) $approvedClaims->where('type', 'maternity')->sum('amount'),
                'pending_count' => $pendingClaims->where('type', 'maternity')->count(),
            ],
        ];

        // Department Cost Summary
        $departments = Department::with(['employees.claims'])->get()->map(function ($dept) {
            $deptClaims = $dept->employees->flatMap->claims;
            $approved = $deptClaims->whereIn('approval_status', ['approved', 'payroll_queued', 'paid']);

            return [
                'name' => $dept->name,
                'total_claims' => $deptClaims->count(),
                'approved_count' => $approved->count(),
                'disbursed_amount' => (float) $approved->sum('amount'),
                'non_taxable_amount' => (float) $approved->sum('non_taxable_amount'),
                'taxable_amount' => (float) $approved->sum('taxable_amount'),
            ];
        })->filter(fn ($d) => $d['total_claims'] > 0);

        $recentRejected = Claim::with(['employee.department'])
            ->where('approval_status', 'rejected')
            ->latest()
            ->take(15)
            ->get();

        $claims = $query->paginate(15)->withQueryString();
        $allDepartments = Department::orderBy('name')->get();

        return view('payroll-benefits.claims.reports', compact(
            'claims',
            'stats',
            'typeBreakdowns',
            'departments',
            'recentRejected',
            'allDepartments',
            'type',
            'departmentId',
            'status',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Export Filtered Claims Audit Report to CSV (docs/payroll.md §3.10)
     */
    public function export(Request $request)
    {
        $type = $request->query('type');
        $departmentId = $request->query('department_id');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $aging = $request->query('aging');

        $query = Claim::with(['employee.department', 'categoryModel'])->latest();

        if ($type) {
            $query->where('type', $type);
        }
        if ($departmentId) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId));
        }
        if ($status) {
            $query->where('approval_status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if ($aging === 'overdue') {
            $query->whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending'])
                ->where('created_at', '<=', now()->subDays(3));
        }

        $filename = 'claims_audit_report_' . ($type ?: 'all') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Receipt Ref',
                'Employee Code',
                'Employee Name',
                'Department',
                'Position',
                'Claim Type',
                'Category',
                'Claimed Amount (PHP)',
                'Non-Taxable Amount (PHP)',
                'Taxable Amount (PHP)',
                'Status',
                'Waiting Days',
                'Filing Date',
                'HR Approved Date',
                'Admin Approved Date',
                'Finance Approved Date',
                'Payroll Queued Date',
                'Rejection Reason',
                'Description / Remarks',
            ]);

            $query->chunk(200, function ($claims) use ($file) {
                foreach ($claims as $c) {
                    fputcsv($file, [
                        $c->receipt_number,
                        $c->employee?->employee_code ?? '—',
                        $c->employee ? ($c->employee->first_name . ' ' . $c->employee->last_name) : '—',
                        $c->employee?->department?->name ?? 'General',
                        $c->employee?->position ?? 'Staff',
                        ucfirst((string) $c->type),
                        $c->categoryModel?->name ?? ($c->category ?? 'General'),
                        number_format((float) $c->amount, 2, '.', ''),
                        number_format((float) $c->non_taxable_amount, 2, '.', ''),
                        number_format((float) $c->taxable_amount, 2, '.', ''),
                        $c->status_label,
                        $c->waitingDays(),
                        $c->created_at?->format('Y-m-d H:i:s'),
                        $c->hr_approved_at?->format('Y-m-d H:i:s') ?? '—',
                        $c->admin_approved_at?->format('Y-m-d H:i:s') ?? '—',
                        $c->finance_approved_at?->format('Y-m-d H:i:s') ?? '—',
                        $c->payroll_queued_at?->format('Y-m-d H:i:s') ?? '—',
                        $c->rejection_reason ?? '—',
                        $c->description ?? '—',
                    ]);
                }
            });

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

}
