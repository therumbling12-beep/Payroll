<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PayrollBatchStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\SalaryComputation;
use App\Models\ThirteenthMonthBatch;
use App\Models\ThirteenthMonthComputation;
use App\Services\PayrollEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollEngineService $payrollEngine
    ) {}

    /**
     * List of All Payroll Cut-offs (Step 1 of 2)
     */
    public function cutoffsList(Request $request): View
    {
        $cutoffs = SalaryComputation::selectRaw('
                cutoff_period,
                COUNT(DISTINCT employee_id) as total_employees,
                SUM(gross_pay) as total_gross,
                SUM(total_deductions) as total_deductions,
                SUM(net_pay) as total_net,
                MAX(created_at) as last_run
            ')
            ->groupBy('cutoff_period')
            ->orderBy('cutoff_period', 'desc')
            ->get();

        // Standard default cutoffs if needed
        if ($cutoffs->isEmpty()) {
            $cutoffs = collect([
                (object)[
                    'cutoff_period' => '2026-07-01_15',
                    'total_employees' => 0,
                    'total_gross' => 0,
                    'total_deductions' => 0,
                    'total_net' => 0,
                    'last_run' => now(),
                ],
                (object)[
                    'cutoff_period' => '2026-07-16_31',
                    'total_employees' => 0,
                    'total_gross' => 0,
                    'total_deductions' => 0,
                    'total_net' => 0,
                    'last_run' => now(),
                ],
            ]);
        }

        $departments = Department::all();
        $batches = PayrollBatch::all()->keyBy('cutoff_period');

        return view('payroll-benefits.payroll.cutoffs-list', compact('cutoffs', 'departments', 'batches'));
    }

    /**
     * Automated Salary Computation Details for a Specific Cutoff (Step 2 of 2)
     */
    public function salaryComputation(Request $request, ?string $cutoff = null): View
    {
        $cutoff = $cutoff ?? $request->query('period', '2026-07-01_15');
        $search = $request->query('search');
        $deptId = $request->query('department');

        $query = SalaryComputation::with(['employee.department', 'aiComplianceLog'])
            ->where('cutoff_period', $cutoff);

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

        $computations = $query->paginate(10)->withQueryString();
        $departments = Department::all();
        $employees = Employee::orderBy('first_name')->get();
        $batch = PayrollBatch::firstOrCreate(
            ['cutoff_period' => $cutoff],
            ['status' => PayrollBatchStatus::DRAFT]
        );

        return view('payroll-benefits.payroll.salary-computation', compact('computations', 'departments', 'employees', 'search', 'deptId', 'cutoff', 'batch'));
    }

    /**
     * Audit Trail & Compliance Page
     */
    public function auditTrail(Request $request): View
    {
        $logs = \App\Models\PayrollAuditTrail::latest()->paginate(15);
        $aiLogs = \App\Models\AiComplianceLog::with('salaryComputation.employee')->latest()->paginate(15);

        return view('payroll-benefits.payroll.audit-trail', compact('logs', 'aiLogs'));
    }

    /**
     * Payslips Page
     */
    public function payslips(Request $request): View
    {
        $search = $request->query('search');
        $deptId = $request->query('department');

        $query = SalaryComputation::with('employee.department');

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

        $computations = $query->paginate(10)->withQueryString();
        $departments = Department::all();

        return view('payroll-benefits.payroll.payslips', compact('computations', 'departments', 'search', 'deptId'));
    }

    /**
     * 13th Month Pay Page
     */
    public function thirteenthMonth(Request $request): View
    {
        $year = (int) $request->query('year', (string) date('Y'));
        $search = $request->query('search');
        $deptId = $request->query('department');

        $query = ThirteenthMonthComputation::with('employee.department')
            ->where('year', $year);

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

        $computations = $query->paginate(10)->withQueryString();
        $departments = Department::all();

        $batch = ThirteenthMonthBatch::firstOrCreate(
            ['year' => $year],
            ['status' => PayrollBatchStatus::DRAFT]
        );

        return view('payroll-benefits.payroll.thirteenth-month', compact('computations', 'departments', 'search', 'deptId', 'year', 'batch'));
    }

    /**
     * Mode of Payment Page
     */
    public function paymentModes(Request $request): View
    {
        $search = $request->query('search');
        $mode = $request->query('mode');

        $query = Employee::with('department');

        if ($search) {
            $query->search($search);
        }

        if ($mode && $mode !== 'all') {
            $query->where('payment_mode', $mode);
        }

        $employees = $query->paginate(10)->withQueryString();

        return view('payroll-benefits.payroll.payment-modes', compact('employees', 'search', 'mode'));
    }

    /**
     * Trigger Batch Calculation
     */
    public function batchCompute(Request $request): RedirectResponse
    {
        $periodInput = $request->input('period', '2026-07-01_15');
        
        if ($periodInput === 'custom') {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            if ($startDate && $endDate) {
                $cutoff = $startDate . '_' . \Carbon\Carbon::parse($endDate)->format('d');
            } else {
                $cutoff = '2026-07-01_15';
            }
        } else {
            $cutoff = $periodInput;
        }

        $deptId = $request->input('department_id');
        $query = Employee::query();

        if ($deptId && $deptId !== 'all') {
            $query->where('department_id', $deptId);
        }

        $employees = $query->get();
        $runAi = $request->boolean('run_ai_audit');

        foreach ($employees as $employee) {
            $computation = $this->payrollEngine->computeForEmployee($employee, $cutoff);
            if ($runAi && $computation) {
                app(\App\Services\GroqAiComplianceService::class)->analyzeCompliance($computation);
            }
        }

        return redirect()->route('payroll.salary-computation.show', $cutoff)
            ->with('status', "Batch computation for period [{$cutoff}] completed successfully for {$employees->count()} employees!");
    }

    /**
     * Store or Override Manual Payroll Computation Entry
     */
    public function storeManual(\App\Http\Requests\ManualPayrollRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $basePay = (float) $validated['base_pay'];
        $tripEarnings = (float) ($validated['trip_earnings'] ?? 0);
        $performanceBonus = (float) ($validated['performance_bonus'] ?? 0);
        $reimbursements = (float) ($validated['reimbursements'] ?? 0);

        $grossPay = $basePay + $tripEarnings + $performanceBonus;

        $sss = (float) $validated['sss_deduction'];
        $philhealth = (float) $validated['philhealth_deduction'];
        $pagibig = (float) $validated['pagibig_deduction'];
        $hmoDeduction = (float) ($validated['hmo_insurance_deduction'] ?? 0);
        $withholdingTax = (float) $validated['withholding_tax'];

        $totalDeductions = $sss + $philhealth + $pagibig + $hmoDeduction + $withholdingTax;
        $netPay = ($grossPay - $totalDeductions) + $reimbursements;

        $computation = SalaryComputation::updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'cutoff_period' => $validated['cutoff_period'],
            ],
            [
                'base_pay' => $basePay,
                'trip_earnings' => $tripEarnings,
                'performance_bonus' => $performanceBonus,
                'reimbursements' => $reimbursements,
                'gross_pay' => $grossPay,
                'sss_deduction' => $sss,
                'philhealth_deduction' => $philhealth,
                'pagibig_deduction' => $pagibig,
                'hmo_insurance_deduction' => $hmoDeduction,
                'withholding_tax' => $withholdingTax,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
                'status' => $validated['status'] ?? 'pending_approval',
            ]
        );

        app(\App\Services\GroqAiComplianceService::class)->analyzeCompliance($computation);

        return redirect()->back()->with('status', 'Manual payroll computation override saved and logged to Audit Trail!');
    }

    /**
     * Workflow Step 1: Submit to Admin
     */
    public function submitToAdmin(Request $request, string $cutoff): RedirectResponse
    {
        $batch = PayrollBatch::firstOrCreate(['cutoff_period' => $cutoff]);
        $batch->update([
            'status' => PayrollBatchStatus::PENDING_ADMIN,
            'submitted_to_admin_at' => now(),
        ]);

        return redirect()->back()->with('status', "Payroll batch [{$cutoff}] submitted to Administrator for approval!");
    }

    /**
     * Workflow Step 2: Admin Approves
     */
    public function approveByAdmin(Request $request, string $cutoff): RedirectResponse
    {
        $batch = PayrollBatch::firstOrCreate(['cutoff_period' => $cutoff]);
        $batch->update([
            'status' => PayrollBatchStatus::APPROVED,
            'approved_by_admin_at' => now(),
        ]);

        SalaryComputation::where('cutoff_period', $cutoff)->update(['status' => 'approved_legal']);

        return redirect()->back()->with('status', "Payroll batch [{$cutoff}] has been approved by Administrator!");
    }

    /**
     * Workflow Step 3: Request Budget from Finance (Totals all net pay)
     */
    public function requestBudget(Request $request, string $cutoff): RedirectResponse
    {
        $batch = PayrollBatch::firstOrCreate(['cutoff_period' => $cutoff]);

        $totalGross = (float) SalaryComputation::where('cutoff_period', $cutoff)->sum('gross_pay');
        $totalDeductions = (float) SalaryComputation::where('cutoff_period', $cutoff)->sum('total_deductions');
        $totalNet = (float) SalaryComputation::where('cutoff_period', $cutoff)->sum('net_pay');

        $batch->update([
            'status' => PayrollBatchStatus::BUDGET_REQUESTED,
            'total_gross' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_net_pay' => $totalNet,
            'budget_requested_at' => now(),
        ]);

        $formattedNet = number_format($totalNet, 2);
        return redirect()->back()->with('status', "Budget of ₱{$formattedNet} requested from Financial Department for period [{$cutoff}]!");
    }

    /**
     * Workflow Step 4: Finance Sends Money / Budget Received
     */
    public function markBudgetReceived(Request $request, string $cutoff): RedirectResponse
    {
        $batch = PayrollBatch::firstOrCreate(['cutoff_period' => $cutoff]);
        $batch->update([
            'status' => PayrollBatchStatus::BUDGET_RECEIVED,
            'budget_received_at' => now(),
        ]);

        $formattedNet = number_format((float) $batch->total_net_pay, 2);
        return redirect()->back()->with('status', "Financial Department transferred ₱{$formattedNet}! Ready for payroll release.");
    }

    /**
     * Workflow Step 5: Release Payroll
     */
    public function releasePayroll(Request $request, string $cutoff): RedirectResponse
    {
        $batch = PayrollBatch::firstOrCreate(['cutoff_period' => $cutoff]);
        $batch->update([
            'status' => PayrollBatchStatus::RELEASED,
            'released_at' => now(),
        ]);

        // Also update individual salary computations status
        SalaryComputation::where('cutoff_period', $cutoff)->update(['status' => 'released_financial']);

        return redirect()->back()->with('status', "Payroll batch [{$cutoff}] successfully released and paid out!");
    }

    /**
     * Trigger 13th Month Pay Batch Calculation
     */
    public function computeThirteenthMonth(Request $request): RedirectResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $employees = Employee::all();

        foreach ($employees as $employee) {
            $monthlySalary = (float) ($employee->monthly_rate ?: ($employee->daily_rate ? $employee->daily_rate * 26 : 25000.00));
            
            $hireYear = (int) $employee->created_at->format('Y');
            $hireMonth = (int) $employee->created_at->format('n');

            if ($hireYear < $year) {
                $monthsWorked = 12;
            } elseif ($hireYear === $year) {
                $monthsWorked = max(1, 12 - $hireMonth + 1);
            } else {
                $monthsWorked = 0;
            }

            $amount = round(($monthlySalary * $monthsWorked) / 12, 2);

            ThirteenthMonthComputation::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'year' => $year,
                ],
                [
                    'monthly_salary' => $monthlySalary,
                    'months_worked' => $monthsWorked,
                    'amount' => $amount,
                    'status' => 'pending_approval',
                ]
            );
        }

        ThirteenthMonthBatch::firstOrCreate(
            ['year' => $year],
            ['status' => PayrollBatchStatus::DRAFT]
        );

        return redirect()->back()->with('status', "13th Month Pay computed for {$employees->count()} employees for Year {$year}!");
    }

    /**
     * 13th Month Workflow: Submit to Admin
     */
    public function submitThirteenthMonthAdmin(Request $request, int $year): RedirectResponse
    {
        $batch = ThirteenthMonthBatch::firstOrCreate(['year' => $year]);
        $batch->update([
            'status' => PayrollBatchStatus::PENDING_ADMIN,
            'submitted_to_admin_at' => now(),
        ]);

        return redirect()->back()->with('status', "13th Month Pay batch for Year {$year} submitted to Administrator for approval!");
    }

    /**
     * 13th Month Workflow: Admin Approves
     */
    public function approveThirteenthMonthAdmin(Request $request, int $year): RedirectResponse
    {
        $batch = ThirteenthMonthBatch::firstOrCreate(['year' => $year]);
        $batch->update([
            'status' => PayrollBatchStatus::APPROVED,
            'approved_by_admin_at' => now(),
        ]);

        ThirteenthMonthComputation::where('year', $year)->update(['status' => 'approved_admin']);

        return redirect()->back()->with('status', "13th Month Pay batch for Year {$year} has been approved by Administrator!");
    }

    /**
     * 13th Month Workflow: Request Budget
     */
    public function requestThirteenthMonthBudget(Request $request, int $year): RedirectResponse
    {
        $batch = ThirteenthMonthBatch::firstOrCreate(['year' => $year]);
        $totalAmount = (float) ThirteenthMonthComputation::where('year', $year)->sum('amount');

        $batch->update([
            'status' => PayrollBatchStatus::BUDGET_REQUESTED,
            'total_amount' => $totalAmount,
            'budget_requested_at' => now(),
        ]);

        $formattedAmount = number_format($totalAmount, 2);
        return redirect()->back()->with('status', "Budget of ₱{$formattedAmount} requested from Financial Department for 13th Month Pay ({$year})!");
    }

    /**
     * 13th Month Workflow: Finance Funds Transferred
     */
    public function markThirteenthMonthBudgetReceived(Request $request, int $year): RedirectResponse
    {
        $batch = ThirteenthMonthBatch::firstOrCreate(['year' => $year]);
        $batch->update([
            'status' => PayrollBatchStatus::BUDGET_RECEIVED,
            'budget_received_at' => now(),
        ]);

        $formattedAmount = number_format((float) $batch->total_amount, 2);
        return redirect()->back()->with('status', "Financial Department transferred ₱{$formattedAmount} for 13th Month Pay!");
    }

    /**
     * 13th Month Workflow: Release Payouts
     */
    public function releaseThirteenthMonth(Request $request, int $year): RedirectResponse
    {
        $batch = ThirteenthMonthBatch::firstOrCreate(['year' => $year]);
        $batch->update([
            'status' => PayrollBatchStatus::RELEASED,
            'released_at' => now(),
        ]);

        ThirteenthMonthComputation::where('year', $year)->update(['status' => 'released']);

        return redirect()->back()->with('status', "13th Month Pay for Year {$year} successfully released and paid out!");
    }
}
