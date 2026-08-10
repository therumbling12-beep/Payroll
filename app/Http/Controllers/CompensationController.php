<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompensationAdjustment;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompensationController extends Controller
{
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

        return view('payroll-benefits.compensation.salary-config', compact('employees', 'departments', 'search', 'deptId'));
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
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $isDriver = str_contains($employee->position, 'Driver');
        $oldRate = $isDriver ? $employee->daily_rate : $employee->monthly_rate;

        CompensationAdjustment::create([
            'employee_id' => $employee->id,
            'type' => $validated['type'],
            'old_rate' => $oldRate,
            'new_rate' => $validated['new_rate'] ?? $oldRate,
            'bonus_amount' => $validated['bonus_amount'] ?? 0.00,
            'old_position' => $employee->position,
            'new_position' => $validated['new_position'] ?? $employee->position,
            'reason' => $validated['reason'],
            'status' => 'pending',
            'effective_date' => now(),
        ]);

        return redirect()->back()->with('status', 'Compensation adjustment proposal created successfully!');
    }

    /**
     * Approve a Compensation Adjustment (Triggers Observer to sync with Employee & Payroll)
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
