<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AccidentClaim;
use App\Models\BudgetRequisition;
use App\Models\Employee;
use App\Models\HmoEnrollment;
use App\Models\SalaryComputation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HmoController extends Controller
{
    /**
     * Employee HMO Coverage & Benefits Sub-Module
     */
    public function plans(Request $request): View
    {
        $search = $request->query('search');

        $query = HmoEnrollment::with('employee.department')->latest();

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->search($search);
            });
        }

        $enrollments = $query->paginate(10)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();

        return view('payroll-benefits.hmo.plans', compact('enrollments', 'employees', 'search'));
    }

    /**
     * Driver Accident Insurance & Benefit Pool Sub-Module
     */
    public function driverInsurance(Request $request): View
    {
        $search = $request->query('search');

        $query = AccidentClaim::with('employee')->latest();

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->search($search);
            });
        }

        $accidentClaims = $query->paginate(10)->withQueryString();
        $employees = Employee::where('position', 'like', '%Driver%')->orderBy('first_name')->get();
        $accumulatedFund = (float) SalaryComputation::sum('hmo_insurance_deduction');

        return view('payroll-benefits.hmo.driver-insurance', compact('accidentClaims', 'employees', 'accumulatedFund', 'search'));
    }

    /**
     * Budget Requisition for HMO & Benefits Sub-Module
     */
    public function budgetRequests(): View
    {
        $requisitions = BudgetRequisition::latest()->paginate(10);

        return view('payroll-benefits.hmo.budget-requests', compact('requisitions'));
    }

    /**
     * Enroll Employee into HMO
     */
    public function enroll(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'provider_plan' => 'required|string|max:255',
            'mbl_amount' => 'required|numeric|min:0',
        ]);

        HmoEnrollment::create([
            'employee_id' => $validated['employee_id'],
            'hmo_card_number' => rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
            'provider_plan' => $validated['provider_plan'],
            'mbl_amount' => $validated['mbl_amount'],
            'status' => 'active',
        ]);

        return redirect()->back()->with('status', 'Employee successfully enrolled into HMO coverage plan!');
    }

    /**
     * File Driver Accident Claim
     */
    public function fileClaim(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'description' => 'required|string|max:1000',
            'bill_amount' => 'required|numeric|min:0.01',
        ]);

        AccidentClaim::create([
            'employee_id' => $validated['employee_id'],
            'incident_number' => 'INCIDENT-' . rand(1000, 9999),
            'description' => $validated['description'],
            'bill_amount' => $validated['bill_amount'],
            'status' => 'paid',
        ]);

        return redirect()->back()->with('status', 'Driver accident emergency claim processed and paid from Fleet Protection Fund.');
    }

    /**
     * Submit Budget Requisition to Financial Management (Team 5)
     */
    public function submitRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'justification' => 'required|string|max:1000',
        ]);

        BudgetRequisition::create([
            'requisition_code' => 'REQ-2026-' . rand(100, 999),
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'justification' => $validated['justification'],
            'status' => 'awaiting_approval',
        ]);

        return redirect()->back()->with('status', 'Budget requisition transmitted to Team 5 (Financial Management)!');
    }
}
