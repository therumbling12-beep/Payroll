<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AccidentClaim;
use App\Models\BudgetRequisition;
use App\Models\Employee;
use App\Models\HmoEnrollment;
use App\Models\SalaryComputation;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /**
     * Performance Analytics Dashboard
     */
    public function performance(): View
    {
        $totalEmployees = Employee::count();
        $totalDrivers = Employee::where('position', 'like', '%Driver%')->count();
        $totalStaff = $totalEmployees - $totalDrivers;

        $totalTripEarnings = (float) SalaryComputation::sum('trip_earnings');
        $totalPerformanceBonuses = (float) SalaryComputation::sum('performance_bonus');
        $avgTripEarningsPerDriver = $totalDrivers > 0 ? round($totalTripEarnings / $totalDrivers, 2) : 0;

        return view('payroll-benefits.analytics.performance', compact(
            'totalEmployees',
            'totalDrivers',
            'totalStaff',
            'totalTripEarnings',
            'totalPerformanceBonuses',
            'avgTripEarningsPerDriver'
        ));
    }

    /**
     * Payroll Cost & Statutory Remittance Analytics
     */
    public function payroll(): View
    {
        $totalGrossPay = (float) SalaryComputation::sum('gross_pay');
        $totalNetPay = (float) SalaryComputation::sum('net_pay');
        $totalDeductions = (float) SalaryComputation::sum('total_deductions');

        $totalSss = (float) SalaryComputation::sum('sss_deduction');
        $totalPhilhealth = (float) SalaryComputation::sum('philhealth_deduction');
        $totalPagibig = (float) SalaryComputation::sum('pagibig_deduction');
        $totalWithholdingTax = (float) SalaryComputation::sum('withholding_tax');

        return view('payroll-benefits.analytics.payroll', compact(
            'totalGrossPay',
            'totalNetPay',
            'totalDeductions',
            'totalSss',
            'totalPhilhealth',
            'totalPagibig',
            'totalWithholdingTax'
        ));
    }

    /**
     * Budget & HMO Benefits Utilization Analytics
     */
    public function budget(): View
    {
        $totalRequisitions = BudgetRequisition::count();
        $totalApprovedBudget = (float) BudgetRequisition::where('status', 'approved')->sum('amount');
        $totalPendingBudget = (float) BudgetRequisition::where('status', 'awaiting_approval')->sum('amount');

        $totalHmoEnrolled = HmoEnrollment::count();
        $totalDriverAccidentFund = (float) SalaryComputation::sum('hmo_insurance_deduction');
        $totalAccidentPayouts = (float) AccidentClaim::sum('bill_amount');

        return view('payroll-benefits.analytics.budget', compact(
            'totalRequisitions',
            'totalApprovedBudget',
            'totalPendingBudget',
            'totalHmoEnrolled',
            'totalDriverAccidentFund',
            'totalAccidentPayouts'
        ));
    }
}
