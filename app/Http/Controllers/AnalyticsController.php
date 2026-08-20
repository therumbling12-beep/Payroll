<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AccidentClaim;
use App\Models\BudgetRequisition;
use App\Models\Claim;
use App\Models\Employee;
use App\Models\SalaryComputation;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /**
     * Executive Overview Analytics Dashboard
     */
    public function overview(): View
    {
        $totalEmployees = Employee::count();
        $totalDrivers = Employee::where('position', 'like', '%Driver%')->count();
        $totalStaff = max(0, $totalEmployees - $totalDrivers);

        $totalGrossPayroll = (float) SalaryComputation::sum('gross_pay');
        $totalNetPayroll = (float) SalaryComputation::sum('net_pay');
        $totalDeductions = (float) SalaryComputation::sum('total_deductions');

        $driverPoolEnrolled = $totalDrivers;
        $totalClaimsDisbursed = (float) Claim::whereIn('approval_status', ['approved', 'payroll_queued', 'paid'])->sum('amount');
        $pendingClaimsCount = Claim::whereIn('approval_status', ['pending_hr', 'pending_admin', 'pending_finance', 'pending'])->count();

        return view('payroll-benefits.analytics.overview', compact(
            'totalEmployees',
            'totalDrivers',
            'totalStaff',
            'totalGrossPayroll',
            'totalNetPayroll',
            'totalDeductions',
            'driverPoolEnrolled',
            'totalClaimsDisbursed',
            'pendingClaimsCount'
        ));
    }
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

        // Top Performers (Highest earnings / bonuses)
        $topPerformers = SalaryComputation::with('employee.department')
            ->orderByDesc('performance_bonus')
            ->orderByDesc('trip_earnings')
            ->take(5)
            ->get();

        // Needs Attention / Low Attendance (Employees with highest lates)
        $needsAttention = \App\Models\Attendance::with('employee.department')
            ->orderByDesc('lates_count')
            ->take(5)
            ->get();

        return view('payroll-benefits.analytics.performance', compact(
            'totalEmployees',
            'totalDrivers',
            'totalStaff',
            'totalTripEarnings',
            'totalPerformanceBonuses',
            'avgTripEarningsPerDriver',
            'topPerformers',
            'needsAttention'
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

        $totalDriverInsuranceMembers = Employee::where('position', 'like', '%Driver%')->where('employment_status', '!=', 'terminated')->count();
        $totalDriverAccidentFund = (float) \App\Models\DriverPoolLedger::where('entry_type', 'driver_contribution')->sum('amount');
        $totalAccidentPayouts = (float) AccidentClaim::sum('bill_amount');

        return view('payroll-benefits.analytics.budget', compact(
            'totalRequisitions',
            'totalApprovedBudget',
            'totalPendingBudget',
            'totalDriverInsuranceMembers',
            'totalDriverAccidentFund',
            'totalAccidentPayouts'
        ));
    }
}
