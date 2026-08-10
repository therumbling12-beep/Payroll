<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryGrade;
use App\Services\CompensationService;
use App\Services\FinancialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompensationController extends Controller
{
    public function __construct(
        protected CompensationService $compensationService,
        protected FinancialService $financialService
    ) {}

    /**
     * Salary Configuration Sub-Module
     */
    public function salaryConfig(Request $request): View
    {
        $search = $request->query('search');
        $deptId = $request->query('department');

        $query = Employee::with('department');

        if ($search) {
            $query->search($search);
        }

        if ($deptId && $deptId !== 'all') {
            $query->department($deptId);
        }

        $employees = $query->paginate(10)->withQueryString();
        $departments = Department::all();
        $salaryGrades = SalaryGrade::all();

        return view('payroll-benefits.compensation.salary-config', compact(
            'employees',
            'departments',
            'salaryGrades',
            'search',
            'deptId'
        ));
    }

    /**
     * Merit & Promotions Sub-Module
     */
    public function meritPromotions(Request $request): View
    {
        $search = $request->query('search');

        $query = CompensationAdjustment::with('employee.department')
            ->where('type', 'merit_promotion')
            ->latest();

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->search($search);
            });
        }

        $adjustments = $query->paginate(10)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();

        return view('payroll-benefits.compensation.merit-promotions', compact('adjustments', 'employees', 'search'));
    }

    /**
     * Counter Offers Sub-Module
     */
    public function counterOffers(Request $request): View
    {
        $search = $request->query('search');

        $query = CompensationAdjustment::with('employee.department')
            ->where('type', 'counter_offer')
            ->latest();

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->search($search);
            });
        }

        $adjustments = $query->paginate(10)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();

        return view('payroll-benefits.compensation.counter-offers', compact('adjustments', 'employees', 'search'));
    }

    /**
     * Submit a new Compensation Adjustment (Merit, Counter Offer, etc)
     */
    public function storeAdjustment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|string|in:merit_promotion,counter_offer,salary_config',
            'new_rate' => 'nullable|numeric|min:0',
            'bonus_amount' => 'nullable|numeric|min:0',
            'new_position' => 'nullable|string|max:255',
            'reason' => 'required|string|max:1000',
            'years_experience' => 'nullable|integer|min:0',
            'certifications_count' => 'nullable|integer|min:0',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $isDriver = str_contains($employee->position, 'Driver');
        $oldRate = $isDriver ? $employee->daily_rate : $employee->monthly_rate;

        $newRate = $validated['new_rate'] ? (float) $validated['new_rate'] : null;

        // Auto Compute Counter Offer if credentials provided and new_rate is empty
        if ($validated['type'] === 'counter_offer' && !$newRate && isset($validated['years_experience'])) {
            $computed = $this->compensationService->computeCounterOffer(
                $validated['new_position'] ?? $employee->position,
                (int) $validated['years_experience'],
                (int) ($validated['certifications_count'] ?? 0)
            );
            $newRate = $computed['computed_counter_offer'];
        }

        $targetRate = $newRate ?? $oldRate;
        $deptName = $employee->department->name ?? 'General';

        // Perform Financial Budget Check
        $budgetCheck = $this->financialService->checkBudgetAvailability($targetRate, $deptName);
        $status = $budgetCheck['approved'] ? 'pending' : 'rejected_financial_budget';

        CompensationAdjustment::create([
            'employee_id' => $employee->id,
            'type' => $validated['type'],
            'old_rate' => $oldRate,
            'new_rate' => $targetRate,
            'bonus_amount' => $validated['bonus_amount'] ?? 0.00,
            'old_position' => $employee->position,
            'new_position' => $validated['new_position'] ?? $employee->position,
            'reason' => $validated['reason'] . ($budgetCheck['approved'] ? '' : " | Failed Financial Budget: {$budgetCheck['reason']}"),
            'status' => $status,
            'effective_date' => now(),
        ]);

        if (!$budgetCheck['approved']) {
            return redirect()->back()->with('error', "Adjustment saved but marked as REJECTED (FINANCIAL BUDGET): {$budgetCheck['reason']}");
        }

        return redirect()->back()->with('status', 'Compensation adjustment proposal created successfully and submitted for approval!');
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
            'proposed_salary' => 'nullable|numeric|min:0',
        ]);

        if (isset($validated['years_experience'])) {
            $result = $this->compensationService->computeCounterOffer(
                $validated['position'],
                (int) $validated['years_experience'],
                (int) ($validated['certifications_count'] ?? 0)
            );
        } else {
            $salary = (float) ($validated['proposed_salary'] ?? 0.00);
            $budgetCheck = $this->financialService->checkBudgetAvailability($salary, 'Operations');
            $result = [
                'position' => $validated['position'],
                'computed_counter_offer' => $salary,
                'financial_budget_check' => $budgetCheck,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Approve a Compensation Adjustment
     */
    public function approveAdjustment(CompensationAdjustment $adjustment): RedirectResponse
    {
        $adjustment->update(['status' => 'approved']);

        return redirect()->back()->with('status', 'Compensation adjustment APPROVED! Employee base rates and bonus updated for next payroll run.');
    }

    /**
     * Reject a Compensation Adjustment
     */
    public function rejectAdjustment(CompensationAdjustment $adjustment): RedirectResponse
    {
        $adjustment->update(['status' => 'rejected']);

        return redirect()->back()->with('status', 'Compensation adjustment rejected.');
    }
}
