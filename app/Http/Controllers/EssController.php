<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Employee;
use App\Models\HmoEnrollment;
use App\Models\SalaryComputation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EssController extends Controller
{
    /**
     * Employee Self-Service (ESS) Portal Dashboard
     */
    public function index(Request $request): View
    {
        $employeeId = $request->query('employee_id');
        $employees = Employee::orderBy('first_name')->get();
        
        $selectedEmployee = $employeeId ? Employee::find($employeeId) : $employees->first();

        $hmo = null;
        $latestComputation = null;
        $claims = collect();

        if ($selectedEmployee) {
            $hmo = HmoEnrollment::where('employee_id', $selectedEmployee->id)->first();
            $latestComputation = SalaryComputation::where('employee_id', $selectedEmployee->id)->latest()->first();
            $claims = Claim::where('employee_id', $selectedEmployee->id)->latest()->get();
        }

        return view('ess.dashboard', compact('employees', 'selectedEmployee', 'hmo', 'latestComputation', 'claims'));
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
}
