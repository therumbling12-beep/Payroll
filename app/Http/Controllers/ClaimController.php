<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Employee;
use App\Services\PayrollEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClaimController extends Controller
{
    /**
     * Expense Reimbursements Sub-Module
     */
    public function expenses(Request $request): View
    {
        $search = $request->query('search');

        $query = Claim::with('employee.department')
            ->where('type', 'expense')
            ->latest();

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->search($search);
            });
        }

        $claims = $query->paginate(10)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();

        return view('payroll-benefits.claims.expenses', compact('claims', 'employees', 'search'));
    }

    /**
     * Driver Incentives Sub-Module
     */
    public function incentives(Request $request): View
    {
        $search = $request->query('search');

        $query = Claim::with('employee.department')
            ->where('type', 'incentive')
            ->latest();

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->search($search);
            });
        }

        $claims = $query->paginate(10)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();

        return view('payroll-benefits.claims.incentives', compact('claims', 'employees', 'search'));
    }

    /**
     * Maternity & Benefit Claims Sub-Module
     */
    public function maternityLeave(Request $request): View
    {
        $search = $request->query('search');

        $query = Claim::with('employee.department')
            ->where('type', 'maternity')
            ->latest();

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->search($search);
            });
        }

        $claims = $query->paginate(10)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();

        return view('payroll-benefits.claims.maternity-leave', compact('claims', 'employees', 'search'));
    }

    /**
     * File a new Claim (Expense, Incentive, Maternity)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|string|in:expense,incentive,maternity',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:1000',
            'receipt_number' => 'nullable|string|max:255',
        ]);

        Claim::create([
            'employee_id' => $validated['employee_id'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'cutoff_period' => '2026-07-01_15',
            'description' => $validated['description'],
            'receipt_number' => $validated['receipt_number'] ?? ('RCP-' . rand(10000, 99999)),
            'status' => 'pending',
            'effective_date' => now(),
        ]);

        return redirect()->back()->with('status', 'Claim request filed successfully!');
    }

    /**
     * Approve Claim & Sync to Payroll Engine
     */
    public function approve(Claim $claim, PayrollEngineService $payrollEngine): RedirectResponse
    {
        $claim->update(['status' => 'approved']);

        // Re-compute employee payroll immediately so claims reflect in real time
        if ($claim->employee) {
            $payrollEngine->computeForEmployee($claim->employee, $claim->cutoff_period);
        }

        return redirect()->back()->with('status', 'Claim APPROVED & synced directly to Employee Payroll!');
    }

    /**
     * Reject Claim
     */
    public function reject(Claim $claim): RedirectResponse
    {
        $claim->update(['status' => 'rejected']);

        return redirect()->back()->with('status', 'Claim request rejected.');
    }
}
