<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OffCycleRunType;
use App\Enums\PayrollBatchStatus;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Holiday;
use App\Models\OffCyclePayroll;
use App\Models\OffCyclePayrollItem;
use App\Models\PayrollAuditTrail;
use App\Models\PayrollBatch;
use App\Models\SalaryComputation;
use App\Models\ThirteenthMonthBatch;
use App\Models\ThirteenthMonthComputation;
use App\Services\Benefits\DriverInsurancePoolService;
use App\Services\Payroll\BirAlphalistService;
use App\Services\Payroll\FinalPaySettlementService;
use App\Services\Payroll\LoanAmortizationService;
use App\Services\Payroll\MinimumWageGuardService;
use App\Services\Payroll\OffCyclePayrollService;
use App\Services\Payroll\PagIbigContributionService;
use App\Services\Payroll\PayrollTransparencyService;
use App\Services\Payroll\PayslipDistributionService;
use App\Services\Payroll\PhilHealthContributionService;
use App\Services\Payroll\SecurityBankExportService;
use App\Services\Payroll\SssContributionService;
use App\Services\Payroll\ThirteenthMonthService;
use App\Services\Payroll\WithholdingTaxService;
use App\Services\PayrollEngineService;
use App\Http\Requests\BatchPayrollUpdateRequest;
use App\Http\Requests\ManualPayrollRequest;
use App\Http\Requests\UpdatePaymentChannelRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        if ($cutoffs->isEmpty()) {
            $cutoffs = collect([
                (object)[
                    'cutoff_period' => '2026-08-06_12',
                    'total_employees' => 0,
                    'total_gross' => 0,
                    'total_deductions' => 0,
                    'total_net' => 0,
                    'last_run' => now(),
                ],
                (object)[
                    'cutoff_period' => '2026-08-13_19',
                    'total_employees' => 0,
                    'total_gross' => 0,
                    'total_deductions' => 0,
                    'total_net' => 0,
                    'last_run' => now(),
                ],
            ]);
        }

        $departments = Department::select(['id', 'name'])->get();
        $batches = PayrollBatch::all()->keyBy('cutoff_period');

        return view('payroll-benefits.payroll.cutoffs-list', compact('cutoffs', 'departments', 'batches'));
    }

    /**
     * Automated Salary Computation Details for a Specific Cutoff (Step 2 of 2)
     */
    public function salaryComputation(Request $request, ?string $cutoff = null): View
    {
        $cutoff = $cutoff ?? $request->query('period', '2026-08-06_12');
        $search = $request->query('search');
        $deptId = $request->query('department');

        $query = SalaryComputation::with(['employee.department', 'aiComplianceLog', 'loanAmortizationLogs.employeeLoan'])
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
        $employees = Employee::select(['id', 'first_name', 'last_name', 'position'])->orderBy('first_name')->get();
        $batch = PayrollBatch::firstOrCreate(
            ['cutoff_period' => $cutoff],
            ['status' => PayrollBatchStatus::DRAFT]
        );

        $startDate = '2026-07-01';
        $endDate = '2026-07-15';
        if (str_contains($cutoff, '_')) {
            [$startPart, $endDay] = explode('_', $cutoff);
            $yearMonth = substr($startPart, 0, 7);
            $startDate = $startPart;
            $endDate = "{$yearMonth}-" . str_pad($endDay, 2, '0', STR_PAD_LEFT);
        }

        $activeHolidays = Holiday::active()
            ->whereBetween('holiday_date', [$startDate, $endDate])
            ->get();

        return view('payroll-benefits.payroll.salary-computation', compact('computations', 'departments', 'employees', 'search', 'deptId', 'cutoff', 'batch', 'activeHolidays'));
    }

    /**
     * Loans & Amortization Management Page (Phase 3)
     */
    public function loansIndex(Request $request): View
    {
        $search = $request->query('search');
        $type = $request->query('type');
        $status = $request->query('status');

        $query = EmployeeLoan::with(['employee.department', 'amortizationLogs']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->search($search);
                  });
            });
        }

        if ($type && $type !== 'all') {
            $query->where('loan_type', $type);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $loans = $query->latest()->paginate(10)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();

        $totalActiveLoans = EmployeeLoan::where('status', 'active')->count();
        $totalActivePortfolio = (float) EmployeeLoan::where('status', 'active')->sum('remaining_balance');
        $totalCollected = (float) EmployeeLoan::sum('total_paid');
        $monthlyAmortizationRecovery = (float) EmployeeLoan::where('status', 'active')->sum('semi_monthly_amortization') * 2;

        return view('payroll-benefits.payroll.loans', compact(
            'loans',
            'employees',
            'search',
            'type',
            'status',
            'totalActiveLoans',
            'totalActivePortfolio',
            'totalCollected',
            'monthlyAmortizationRecovery'
        ));
    }

    /**
     * Store New Employee Loan
     */
    public function storeLoan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'loan_type' => ['required', 'string', 'in:sss_salary_loan,sss_calamity_loan,hdmf_multi_purpose_loan,hdmf_housing_loan,company_emergency_loan,cash_advance'],
            'reference_no' => ['required', 'string', 'max:50', 'unique:employee_loans,reference_no'],
            'principal_amount' => ['required', 'numeric', 'min:1'],
            'total_amount_due' => ['required', 'numeric', 'min:1'],
            'term_months' => ['required', 'integer', 'min:1', 'max:120'],
            'semi_monthly_amortization' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $validated['total_paid'] = 0.00;
        $validated['remaining_balance'] = (float) $validated['total_amount_due'];
        $validated['status'] = 'active';

        EmployeeLoan::create($validated);

        return redirect()->back()->with('status', "Employee loan [{$validated['reference_no']}] successfully registered and scheduled for amortization.");
    }

    /**
     * Pause Employee Loan Amortization
     */
    public function pauseLoan(EmployeeLoan $loan): RedirectResponse
    {
        $loan->update(['status' => 'paused']);
        return redirect()->back()->with('status', "Loan [{$loan->reference_no}] has been temporarily paused from payroll deductions.");
    }

    /**
     * Resume Employee Loan Amortization
     */
    public function resumeLoan(EmployeeLoan $loan): RedirectResponse
    {
        $loan->update(['status' => 'active']);
        return redirect()->back()->with('status', "Loan [{$loan->reference_no}] has been resumed for automated payroll deductions.");
    }

    /**
     * Off-Cycle Payroll & Final Pay Settlements Dashboard (Phase 5)
     */
    public function offCycleIndex(Request $request): View
    {
        $search = $request->query('search');
        $type = $request->query('type');
        $status = $request->query('status');

        $query = OffCyclePayroll::with('items.employee.department');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('run_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($type && $type !== 'all') {
            $query->where('run_type', $type);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $runs = $query->latest()->paginate(10)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();

        $totalRuns = OffCyclePayroll::count();
        $totalDisbursed = (float) OffCyclePayroll::where('status', 'released')->sum('total_net_pay');
        $pendingApprovals = OffCyclePayroll::where('status', 'draft')->count();

        return view('payroll-benefits.payroll.off-cycle', compact(
            'runs',
            'employees',
            'search',
            'type',
            'status',
            'totalRuns',
            'totalDisbursed',
            'pendingApprovals'
        ));
    }

    /**
     * Store New Off-Cycle Run (Final Pay or Special Run)
     */
    public function storeOffCycle(Request $request): RedirectResponse
    {
        $runType = $request->input('run_type', 'final_pay');
        $offCycleService = app(OffCyclePayrollService::class);

        if ($runType === 'final_pay') {
            $validated = $request->validate([
                'employee_id' => ['required', 'exists:employees,id'],
                'separation_date' => ['required', 'date'],
                'payout_date' => ['required', 'date'],
                'unpaid_days' => ['nullable', 'numeric', 'min:0'],
                'unused_leaves' => ['nullable', 'numeric', 'min:0'],
                'other_deductions' => ['nullable', 'numeric', 'min:0'],
                'reimbursements' => ['nullable', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            $run = $offCycleService->createFinalPayRun($validated);

            return redirect()->route('payroll.off-cycle.show', $run->id)
                ->with('status', "Final Pay Settlement [{$run->run_number}] created successfully.");
        }

        $validated = $request->validate([
            'run_type' => ['required', Rule::enum(OffCycleRunType::class)],
            'title' => ['required', 'string', 'max:150'],
            'payout_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['exists:employees,id'],
            'amounts' => ['required', 'array', 'min:1'],
            'amounts.*' => ['numeric', 'min:0.01'],
        ]);

        $items = [];
        foreach ($validated['employee_ids'] as $idx => $empId) {
            $items[] = [
                'employee_id' => (int) $empId,
                'amount' => (float) $validated['amounts'][$idx],
            ];
        }

        $run = $offCycleService->createSpecialRun([
            'run_type' => $validated['run_type'],
            'title' => $validated['title'],
            'payout_date' => $validated['payout_date'],
            'notes' => $validated['notes'] ?? null,
            'items' => $items,
        ]);

        return redirect()->route('payroll.off-cycle.show', $run->id)
            ->with('status', "Off-Cycle Run [{$run->run_number}] created successfully.");
    }

    /**
     * Show Off-Cycle Batch Details
     */
    public function showOffCycle(OffCyclePayroll $offCycle): View
    {
        $offCycle->load(['items.employee.department']);
        return view('payroll-benefits.payroll.off-cycle-show', compact('offCycle'));
    }

    /**
     * Approve Off-Cycle Run
     */
    public function approveOffCycle(OffCyclePayroll $offCycle): RedirectResponse
    {
        app(OffCyclePayrollService::class)->approveRun($offCycle);
        return redirect()->back()->with('status', "Off-Cycle Run [{$offCycle->run_number}] approved successfully.");
    }

    /**
     * Release Off-Cycle Run
     */
    public function releaseOffCycle(OffCyclePayroll $offCycle): RedirectResponse
    {
        app(OffCyclePayrollService::class)->releaseRun($offCycle);
        return redirect()->back()->with('status', "Off-Cycle Run [{$offCycle->run_number}] released and disbursed. Active loan offsets applied.");
    }

    /**
     * View Printable Final Pay Settlement & Quitclaim Certificate
     */
    public function settlementCertificate(OffCyclePayrollItem $item): View
    {
        $item->load(['employee.department', 'offCyclePayroll']);
        return view('payroll-benefits.payroll.settlement-certificate', compact('item'));
    }

    /**
     * Get Complete Mathematical Breakdown and Formula Transparency for Final Pay Off-Cycle Item (Transparency Phase C)
     */
    public function offCycleItemTransparency(OffCyclePayrollItem $item): \Illuminate\Http\JsonResponse
    {
        $breakdown = app(FinalPaySettlementService::class)->generateDetailedBreakdown($item);
        return response()->json($breakdown);
    }

    /**
     * Export Off-Cycle CSV
     */
    public function exportOffCycleCsv(OffCyclePayroll $offCycle): StreamedResponse
    {
        $offCycle->load(['items.employee.department']);
        $filename = "Off_Cycle_Register_{$offCycle->run_number}.csv";

        return response()->streamDownload(function () use ($offCycle) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'RUN_NUMBER',
                'RUN_TYPE',
                'EMPLOYEE_CODE',
                'EMPLOYEE_NAME',
                'DEPARTMENT',
                'POSITION',
                'BASIC_PAY_EARNED',
                'PRO_RATED_13TH_MONTH',
                'LEAVE_CONVERSION_PAY',
                'BONUSES_DIFFERENTIALS',
                'REIMBURSEMENTS',
                'GROSS_AMOUNT',
                'LOAN_DEDUCTION',
                'OTHER_DEDUCTIONS',
                'TOTAL_DEDUCTIONS',
                'NET_PAYOUT',
                'PAYOUT_DATE',
            ]);

            foreach ($offCycle->items as $item) {
                $emp = $item->employee;
                fputcsv($handle, [
                    $offCycle->run_number,
                    $offCycle->run_type->value,
                    $emp?->employee_code ?? 'N/A',
                    ($emp?->first_name ?? '') . ' ' . ($emp?->last_name ?? ''),
                    $emp?->department?->name ?? 'N/A',
                    $emp?->position ?? 'N/A',
                    number_format((float) $item->basic_pay_earned, 2, '.', ''),
                    number_format((float) $item->pro_rated_13th_month, 2, '.', ''),
                    number_format((float) $item->leave_conversion_pay, 2, '.', ''),
                    number_format((float) $item->bonuses_differentials, 2, '.', ''),
                    number_format((float) $item->reimbursements, 2, '.', ''),
                    number_format((float) $item->gross_amount, 2, '.', ''),
                    number_format((float) $item->loan_deduction, 2, '.', ''),
                    number_format((float) $item->other_deductions, 2, '.', ''),
                    number_format((float) $item->total_deductions, 2, '.', ''),
                    number_format((float) $item->net_settlement_pay, 2, '.', ''),
                    $offCycle->payout_date->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Audit Trail & Compliance Page
     */
    public function auditTrail(Request $request): View
    {
        $logs = PayrollAuditTrail::latest()->paginate(15);
        $aiLogs = \App\Models\AiComplianceLog::with('salaryComputation.employee')->latest()->paginate(15);

        return view('payroll-benefits.payroll.audit-trail', compact('logs', 'aiLogs'));
    }

    /**
     * Payslips Overview — Redirects to Unified Salary Computation Desk (Phase 6)
     */
    public function payslips(Request $request): RedirectResponse
    {
        $period = $request->query('period');
        if ($period) {
            return redirect()->route('payroll.salary-computation.show', $period);
        }

        return redirect()->route('payroll.salary-computation');
    }

    /**
     * Show Individual Printable Payslip (Phase 6)
     */
    public function showPayslip(SalaryComputation $computation): View
    {
        $payslipService = app(PayslipDistributionService::class);
        $payslip = $payslipService->formatPayslipData($computation);

        return view('payroll-benefits.payroll.payslip-printable', compact('payslip', 'computation'));
    }

    /**
     * Get Complete Mathematical Breakdown and Formula Transparency for a Computation (Transparency Phase 1)
     */
    public function computationTransparency(SalaryComputation $computation): \Illuminate\Http\JsonResponse
    {
        $breakdown = app(PayrollTransparencyService::class)->generateBreakdown($computation);
        return response()->json($breakdown);
    }

    /**
     * Show Batch Printable Payslips for Entire Cutoff (Phase 6)
     */
    public function printBatchPayslips(Request $request, string $cutoff): View
    {
        $deptId = $request->query('department') ? (int) $request->query('department') : null;
        $payslipService = app(PayslipDistributionService::class);
        $batchPayslips = $payslipService->formatBatchPayslipData($cutoff, $deptId);

        return view('payroll-benefits.payroll.payslips-batch-printable', compact('batchPayslips', 'cutoff'));
    }

    /**
     * Export Cash Voucher Register CSV for Unbanked Workers (Phase 6)
     */
    public function exportCashVoucher(Request $request, string $cutoff): StreamedResponse
    {
        $computations = SalaryComputation::with('employee.department')
            ->where('cutoff_period', $cutoff)
            ->get();

        $csv = app(SecurityBankExportService::class)->generateCashVoucherCsv($computations, $cutoff);
        $filename = "Cash_Voucher_Register_{$cutoff}.csv";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Push Payslips to Employee Self-Service (ESS) Portal (Phase 6)
     */
    public function pushPayslipsToEss(Request $request, string $cutoff): RedirectResponse
    {
        $count = SalaryComputation::where('cutoff_period', $cutoff)->count();

        PayrollAuditTrail::create([
            'user_name' => 'HR Operations & Payroll Officer',
            'action' => 'PUSH_PAYSLIPS_ESS',
            'model_type' => 'SalaryComputation',
            'model_id' => null,
            'old_values' => ['cutoff_period' => $cutoff, 'status' => 'draft'],
            'new_values' => ['cutoff_period' => $cutoff, 'status' => 'published_ess', 'count' => $count],
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return redirect()->back()->with('status', "Digital payslips for cutoff [{$cutoff}] have been successfully published and pushed to Employee Self-Service (ESS) portal for {$count} personnel.");
    }

    /**
     * 13th Month Pay Page
     */
    public function thirteenthMonth(Request $request): View
    {
        $year = (int) $request->query('year', 2026);
        $search = $request->query('search');
        $deptId = $request->query('department');
        $availableYears = [2026, 2027, 2028];

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

        return view('payroll-benefits.payroll.thirteenth-month', compact('computations', 'departments', 'search', 'deptId', 'year', 'batch', 'availableYears'));
    }

    /**
     * Get Detailed 12-Month Cutoff Audit Ledger & Mathematical Transparency for 13th Month Pay (Transparency Phase B)
     */
    public function thirteenthMonthTransparency(int $year, Employee $employee): \Illuminate\Http\JsonResponse
    {
        $breakdown = app(ThirteenthMonthService::class)->getDetailedAuditLedger($year, $employee->id);
        return response()->json($breakdown);
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
        $periodInput = $request->input('period', '2026-08-13_19');
        
        if ($periodInput === 'custom') {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            if ($startDate && $endDate) {
                $cutoff = $startDate . '_' . Carbon::parse($endDate)->format('d');
            } else {
                $cutoff = '2026-08-13_19';
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
            ->with('status', "Batch computation for period [{$cutoff}] completed successfully for {$employees->count()} employees.");
    }

    /**
     * Store or Override Manual Payroll Computation Entry
     */
    public function storeManual(\App\Http\Requests\ManualPayrollRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $employee = Employee::find($validated['employee_id']);
        $isDriver = $employee && str_contains(strtolower($employee->position ?? ''), 'driver');

        $basePay = (float) ($validated['base_pay'] ?? 0);
        $tripEarnings = (float) ($validated['trip_earnings'] ?? 0);
        $driverTripIncentive = 0.00;
        $holidayPay = (float) ($validated['holiday_pay'] ?? 0);
        $overtimePay = (float) ($validated['overtime_pay'] ?? 0);
        $nightDiffPay = (float) ($validated['night_diff_pay'] ?? 0);
        $performanceBonus = (float) ($validated['performance_bonus'] ?? 0);
        $reimbursements = (float) ($validated['reimbursements'] ?? 0);

        $grossPay = round($basePay + $tripEarnings + $driverTripIncentive + $holidayPay + $overtimePay + $nightDiffPay + $performanceBonus, 2);

        $statutoryMinBasis = (float) CompanySetting::getValue('statutory_minimum_monthly_basis', 10000.00);
        $platformFeeRate = (float) CompanySetting::getValue('driver_tnc_platform_fee_rate', 0.20);

        $monthlyBasis = max($statutoryMinBasis, $employee?->monthly_rate ?: ($grossPay * 2));
        $sssRes = app(SssContributionService::class)->compute($monthlyBasis, true);
        $philRes = app(PhilHealthContributionService::class)->compute($monthlyBasis, true);
        $pagibigRes = app(PagIbigContributionService::class)->compute($monthlyBasis, true);

        $sss = isset($validated['sss_deduction']) ? (float) $validated['sss_deduction'] : $sssRes['employee_share'];
        $philhealth = isset($validated['philhealth_deduction']) ? (float) $validated['philhealth_deduction'] : $philRes['employee_share'];
        $pagibig = isset($validated['pagibig_deduction']) ? (float) $validated['pagibig_deduction'] : $pagibigRes['employee_share'];
        $platformFee = $isDriver ? round($tripEarnings * $platformFeeRate, 2) : 0.00;
        $loanDeduction = (float) ($validated['loan_deduction'] ?? 0);
        $tardinessDeduction = (float) ($validated['tardiness_deduction'] ?? 0);
        $undertimeDeduction = (float) ($validated['undertime_deduction'] ?? 0);

        $taxableIncome = max(0.00, $grossPay - ($sss + $philhealth + $pagibig + $tardinessDeduction + $undertimeDeduction));
        $withholdingTax = isset($validated['withholding_tax']) 
            ? (float) $validated['withholding_tax'] 
            : app(WithholdingTaxService::class)->compute($taxableIncome, false, true);

        $totalDeductions = round($sss + $philhealth + $pagibig + $platformFee + $loanDeduction + $withholdingTax + $tardinessDeduction + $undertimeDeduction, 2);
        $netPay = round($grossPay - $totalDeductions, 2);

        $computation = SalaryComputation::updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'cutoff_period' => $validated['cutoff_period'],
            ],
            [
                'base_pay' => $basePay,
                'trip_earnings' => $tripEarnings,
                'driver_trip_incentive' => $driverTripIncentive,
                'holiday_pay' => $holidayPay,
                'overtime_pay' => $overtimePay,
                'night_diff_pay' => $nightDiffPay,
                'performance_bonus' => $performanceBonus,
                'reimbursements' => $reimbursements,
                'gross_pay' => $grossPay,
                'sss_deduction' => $sss,
                'sss_employer' => $sssRes['employer_share'],
                'philhealth_deduction' => $philhealth,
                'philhealth_employer' => $philRes['employer_share'],
                'pagibig_deduction' => $pagibig,
                'pagibig_employer' => $pagibigRes['employer_share'],
                'ec_contribution' => $sssRes['ec_contribution'],
                'platform_fee_deduction' => $platformFee,
                'loan_deduction' => $loanDeduction,
                'withholding_tax' => $withholdingTax,
                'tardiness_deduction' => $tardinessDeduction,
                'undertime_deduction' => $undertimeDeduction,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
                'status' => $validated['status'] ?? 'pending_approval',
            ]
        );

        app(\App\Services\GroqAiComplianceService::class)->analyzeCompliance($computation);

        return redirect()->back()->with('status', 'Manual payroll computation override saved and logged to Audit Trail.');
    }

    /**
     * Save batch manual payroll encodings and statutory deductions atomically.
     */
    public function batchUpdateManual(BatchPayrollUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $cutoff = $validated['cutoff_period'];

        DB::transaction(function () use ($validated, $cutoff) {
            foreach ($validated['computations'] as $row) {
                $comp = SalaryComputation::findOrFail($row['id']);
                
                $basePay = (float) $row['base_pay'];
                $tripEarnings = (float) ($row['trip_earnings'] ?? 0);
                $tripIncentive = 0.00;
                $otPay = (float) ($row['overtime_pay'] ?? 0);
                $holidayPay = (float) ($row['holiday_pay'] ?? 0);
                $nightDiff = (float) ($row['night_diff_pay'] ?? 0);
                $reimbursements = (float) ($row['reimbursements'] ?? 0);

                $grossPay = round($basePay + $tripEarnings + $tripIncentive + $otPay + $holidayPay + $nightDiff, 2);

                $sss = (float) $row['sss_deduction'];
                $philhealth = (float) $row['philhealth_deduction'];
                $pagibig = (float) $row['pagibig_deduction'];
                $loan = (float) ($row['loan_deduction'] ?? 0);
                $tardy = (float) ($row['tardiness_deduction'] ?? 0);
                $undertime = (float) ($row['undertime_deduction'] ?? 0);

                $taxableIncome = max(0.00, $grossPay - ($sss + $philhealth + $pagibig + $tardy + $undertime));
                $withholdingTax = app(WithholdingTaxService::class)->compute($taxableIncome, false, true);

                $totalDeductions = round($sss + $philhealth + $pagibig + $loan + $withholdingTax + $tardy + $undertime, 2);
                $netPay = round($grossPay - $totalDeductions, 2);

                $comp->update([
                    'base_pay' => $basePay,
                    'trip_earnings' => $tripEarnings,
                    'driver_trip_incentive' => $tripIncentive,
                    'overtime_pay' => $otPay,
                    'holiday_pay' => $holidayPay,
                    'night_diff_pay' => $nightDiff,
                    'reimbursements' => $reimbursements,
                    'gross_pay' => $grossPay,
                    'sss_deduction' => $sss,
                    'philhealth_deduction' => $philhealth,
                    'pagibig_deduction' => $pagibig,
                    'loan_deduction' => $loan,
                    'tardiness_deduction' => $tardy,
                    'undertime_deduction' => $undertime,
                    'withholding_tax' => $withholdingTax,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $netPay,
                ]);
            }

            PayrollAuditTrail::create([
                'user_name' => auth()->user()?->name ?? 'HR Operations Officer',
                'action' => 'MANUAL_PAYROLL_BATCH_UPDATED',
                'model_type' => SalaryComputation::class,
                'model_id' => null,
                'new_values' => ['cutoff_period' => $cutoff, 'rows_updated' => count($validated['computations'])],
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);
        });

        return redirect()->route('payroll.salary-computation.show', $cutoff)
            ->with('status', 'Manual payroll encodings and statutory deductions saved successfully.');
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

        return redirect()->back()->with('status', "Payroll batch [{$cutoff}] submitted to Administrator for approval.");
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

        return redirect()->back()->with('status', "Payroll batch [{$cutoff}] has been approved by Administrator.");
    }

    /**
     * Workflow Step 3: Request Budget from Finance
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
        return redirect()->back()->with('status', "Budget of PHP {$formattedNet} requested from Financial Department for period [{$cutoff}].");
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
        return redirect()->back()->with('status', "Financial Department transferred PHP {$formattedNet}. Ready for payroll release.");
    }

    /**
     * Workflow Step 5: Release Payroll & Apply Loan Amortizations
     */
    public function releasePayroll(Request $request, string $cutoff): RedirectResponse
    {
        $batch = PayrollBatch::firstOrCreate(['cutoff_period' => $cutoff]);
        $batch->update([
            'status' => PayrollBatchStatus::RELEASED,
            'released_at' => now(),
        ]);

        $computations = SalaryComputation::where('cutoff_period', $cutoff)->get();
        $loanService = app(LoanAmortizationService::class);
        $driverPoolService = app(DriverInsurancePoolService::class);

        foreach ($computations as $computation) {
            $computation->update(['status' => 'released_financial']);
            $loanService->applyDeductions($computation);
            $driverPoolService->recordPayrollContribution($computation);
        }

        return redirect()->back()->with('status', "Payroll batch [{$cutoff}] successfully released and paid out. Loan amortizations and driver pool contributions recorded to ledger.");
    }

    /**
     * Trigger 13th Month Pay Batch Calculation
     */
    public function computeThirteenthMonth(Request $request): RedirectResponse
    {
        $year = (int) $request->input('year', 2026);
        $service = app(ThirteenthMonthService::class);
        $annualData = $service->computeAnnual($year);

        foreach ($annualData['employees'] as $row) {
            ThirteenthMonthComputation::updateOrCreate(
                [
                    'employee_id' => $row['employee_id'],
                    'year' => $year,
                ],
                [
                    'monthly_salary' => $row['monthly_salary'],
                    'months_worked' => $row['months_worked'],
                    'amount' => $row['amount'],
                    'status' => 'pending_approval',
                ]
            );
        }

        ThirteenthMonthBatch::firstOrCreate(
            ['year' => $year],
            ['status' => PayrollBatchStatus::DRAFT]
        );

        $count = count($annualData['employees']);
        return redirect()->back()->with('status', "13th Month Pay computed for {$count} employees for Year {$year}.");
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

        return redirect()->back()->with('status', "13th Month Pay batch for Year {$year} submitted to Administrator for approval.");
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

        return redirect()->back()->with('status', "13th Month Pay batch for Year {$year} has been approved by Administrator.");
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
        return redirect()->back()->with('status', "Budget of PHP {$formattedAmount} requested from Financial Department for 13th Month Pay ({$year}).");
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
        return redirect()->back()->with('status', "Financial Department transferred PHP {$formattedAmount} for 13th Month Pay.");
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

        return redirect()->back()->with('status', "13th Month Pay for Year {$year} successfully released and paid out.");
    }

    /**
     * Company-Wide Payroll Summary Reports & DOLE/BIR Remittances (Phase 4)
     */
    public function reports(Request $request): View
    {
        $cutoffs = SalaryComputation::select('cutoff_period')
            ->distinct()
            ->orderBy('cutoff_period', 'desc')
            ->get();

        $defaultCutoff = $cutoffs->first()?->cutoff_period ?? '2026-08-06_12';
        $cutoff = $request->query('period', $defaultCutoff);
        $deptId = $request->query('department');
        $year = (int) $request->query('year', (string) date('Y'));

        $query = SalaryComputation::with(['employee.department'])
            ->where('cutoff_period', $cutoff);

        if ($deptId && $deptId !== 'all') {
            $query->whereHas('employee', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        $computations = $query->get();
        $departments = Department::all();

        $sssSummary = [
            'total_msc' => max(5000.00, (float) $computations->sum('gross_pay') * 2),
            'total_ee' => (float) $computations->sum('sss_deduction'),
            'total_er' => (float) $computations->sum('sss_employer'),
            'total_ec' => (float) $computations->sum('ec_contribution'),
            'grand_total' => (float) $computations->sum('sss_deduction') + (float) $computations->sum('sss_employer') + (float) $computations->sum('ec_contribution'),
        ];

        $philhealthSummary = [
            'total_mbs' => (float) $computations->sum('base_pay') * 2,
            'total_ee' => (float) $computations->sum('philhealth_deduction'),
            'total_er' => (float) $computations->sum('philhealth_employer'),
            'grand_total' => (float) $computations->sum('philhealth_deduction') + (float) $computations->sum('philhealth_employer'),
        ];

        $pagibigSummary = [
            'total_compensation' => (float) $computations->sum('gross_pay') * 2,
            'total_ee' => (float) $computations->sum('pagibig_deduction'),
            'total_er' => (float) $computations->sum('pagibig_employer'),
            'grand_total' => (float) $computations->sum('pagibig_deduction') + (float) $computations->sum('pagibig_employer'),
        ];

        $alphalist = app(BirAlphalistService::class)->computeAlphalist($year);
        $wageCompliance = app(MinimumWageGuardService::class)->evaluateCompliance();

        return view('payroll-benefits.payroll.reports', compact(
            'computations',
            'cutoffs',
            'cutoff',
            'departments',
            'deptId',
            'year',
            'sssSummary',
            'philhealthSummary',
            'pagibigSummary',
            'alphalist',
            'wageCompliance'
        ));
    }

    /**
     * Export BIR Form 1604-C Alphalist Schedule 7.1 CSV
     */
    public function exportBirAlphalist(Request $request, int $year): StreamedResponse
    {
        $alphalist = app(BirAlphalistService::class)->computeAlphalist($year);
        $filename = "BIR_1604C_Alphalist_Schedule_7.1_{$year}.csv";

        return response()->streamDownload(function () use ($alphalist, $year) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'TAX_YEAR',
                'EMPLOYEE_CODE',
                'TIN',
                'EMPLOYEE_NAME',
                'DEPARTMENT',
                'POSITION',
                'GROSS_COMPENSATION',
                'NON_TAXABLE_STATUTORY_DEDUCTIONS',
                'EXEMPT_13TH_MONTH_AND_BENEFITS',
                'TAXABLE_COMPENSATION_INCOME',
                'ANNUAL_TAX_DUE',
                'TOTAL_TAX_WITHHELD_TO_DATE',
                'YEAR_END_ADJUSTMENT',
                'ADJUSTMENT_TYPE',
            ]);

            foreach ($alphalist['employees'] as $row) {
                fputcsv($handle, [
                    $year,
                    $row['employee_code'],
                    $row['tin'],
                    $row['full_name'],
                    $row['department'],
                    $row['position'],
                    number_format((float) $row['gross_compensation'], 2, '.', ''),
                    number_format((float) $row['non_taxable_statutory'], 2, '.', ''),
                    number_format((float) $row['exempt_thirteenth_month'], 2, '.', ''),
                    number_format((float) $row['taxable_compensation'], 2, '.', ''),
                    number_format((float) $row['annual_tax_due'], 2, '.', ''),
                    number_format((float) $row['tax_withheld'], 2, '.', ''),
                    number_format((float) $row['tax_adjustment'], 2, '.', ''),
                    strtoupper($row['adjustment_type']),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export Master Payroll Register CSV
     */
    public function exportPayrollRegister(Request $request, string $cutoff): StreamedResponse
    {
        $computations = SalaryComputation::with('employee.department')
            ->where('cutoff_period', $cutoff)
            ->get();

        $filename = "Master_Payroll_Register_{$cutoff}.csv";

        return response()->streamDownload(function () use ($computations, $cutoff) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Cutoff Period',
                'Employee Code',
                'Full Name',
                'Department',
                'Position',
                'Base Pay',
                'Trip Earnings',
                'Driver Trip Incentives',
                'Holiday Pay',
                'Overtime Pay',
                'Night Shift Diff',
                'Bonus & Incentives',
                'Reimbursements (Non-Taxable)',
                'Gross Pay',
                'SSS (Employee)',
                'PhilHealth (Employee)',
                'Pag-IBIG (Employee)',
                'TNC Platform Fee',
                'Loan Amortization Deductions',
                'BIR Withholding Tax',
                'Tardiness Deduction',
                'Undertime Deduction',
                'Total Deductions',
                'Net Pay Payout',
                'SSS (Employer)',
                'PhilHealth (Employer)',
                'Pag-IBIG (Employer)',
                'EC Contribution (Employer)',
            ]);

            foreach ($computations as $c) {
                $emp = $c->employee;
                fputcsv($handle, [
                    $cutoff,
                    $emp?->employee_code ?? 'N/A',
                    ($emp?->first_name ?? '') . ' ' . ($emp?->last_name ?? ''),
                    $emp?->department?->name ?? 'N/A',
                    $emp?->position ?? 'N/A',
                    number_format((float) $c->base_pay, 2, '.', ''),
                    number_format((float) $c->trip_earnings, 2, '.', ''),
                    number_format((float) ($c->driver_trip_incentive ?? 0), 2, '.', ''),
                    number_format((float) $c->holiday_pay, 2, '.', ''),
                    number_format((float) $c->overtime_pay, 2, '.', ''),
                    number_format((float) $c->night_diff_pay, 2, '.', ''),
                    number_format((float) $c->performance_bonus, 2, '.', ''),
                    number_format((float) $c->reimbursements, 2, '.', ''),
                    number_format((float) $c->gross_pay, 2, '.', ''),
                    number_format((float) $c->sss_deduction, 2, '.', ''),
                    number_format((float) $c->philhealth_deduction, 2, '.', ''),
                    number_format((float) $c->pagibig_deduction, 2, '.', ''),
                    number_format((float) $c->platform_fee_deduction, 2, '.', ''),
                    number_format((float) ($c->loan_deduction ?? 0), 2, '.', ''),
                    number_format((float) $c->withholding_tax, 2, '.', ''),
                    number_format((float) $c->tardiness_deduction, 2, '.', ''),
                    number_format((float) $c->undertime_deduction, 2, '.', ''),
                    number_format((float) $c->total_deductions, 2, '.', ''),
                    number_format((float) $c->net_pay, 2, '.', ''),
                    number_format((float) ($c->sss_employer ?? $c->sss_deduction * 2), 2, '.', ''),
                    number_format((float) ($c->philhealth_employer ?? $c->philhealth_deduction), 2, '.', ''),
                    number_format((float) ($c->pagibig_employer ?? $c->pagibig_deduction), 2, '.', ''),
                    number_format((float) ($c->ec_contribution ?? 10.00), 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export SSS R-3 Contribution Collection Schedule CSV
     */
    public function exportSssRemittance(Request $request, string $cutoff): StreamedResponse
    {
        $computations = SalaryComputation::with('employee')
            ->where('cutoff_period', $cutoff)
            ->get();

        $filename = "SSS_R3_Remittance_Schedule_{$cutoff}.csv";

        return response()->streamDownload(function () use ($computations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'SS_NUMBER',
                'EMPLOYEE_NAME',
                'MONTHLY_SALARY_CREDIT',
                'EMPLOYEE_SHARE',
                'EMPLOYER_SHARE',
                'EC_CONTRIBUTION',
                'TOTAL_SSS_REMITTANCE',
            ]);

            foreach ($computations as $c) {
                $emp = $c->employee;
                $msc = max(5000.00, round((float)$c->gross_pay * 2 / 500) * 500);
                $ee = (float) $c->sss_deduction;
                $er = (float) ($c->sss_employer ?? $ee * 2);
                $ec = (float) ($c->ec_contribution ?? 10.00);

                fputcsv($handle, [
                    '00-0000000-0',
                    strtoupper(($emp?->first_name ?? '') . ' ' . ($emp?->last_name ?? '')),
                    number_format($msc, 2, '.', ''),
                    number_format($ee, 2, '.', ''),
                    number_format($er, 2, '.', ''),
                    number_format($ec, 2, '.', ''),
                    number_format($ee + $er + $ec, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export PhilHealth RF-1 Remittance Schedule CSV
     */
    public function exportPhilHealthRemittance(Request $request, string $cutoff): StreamedResponse
    {
        $computations = SalaryComputation::with('employee')
            ->where('cutoff_period', $cutoff)
            ->get();

        $filename = "PhilHealth_RF1_Remittance_Schedule_{$cutoff}.csv";

        return response()->streamDownload(function () use ($computations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'PHILHEALTH_NUMBER',
                'EMPLOYEE_NAME',
                'MONTHLY_BASIC_SALARY',
                'EMPLOYEE_PREMIUM',
                'EMPLOYER_PREMIUM',
                'TOTAL_PHILHEALTH_PREMIUM',
            ]);

            foreach ($computations as $c) {
                $emp = $c->employee;
                $ee = (float) $c->philhealth_deduction;
                $er = (float) ($c->philhealth_employer ?? $ee);

                fputcsv($handle, [
                    '00-000000000-0',
                    strtoupper(($emp?->first_name ?? '') . ' ' . ($emp?->last_name ?? '')),
                    number_format((float)$c->base_pay * 2, 2, '.', ''),
                    number_format($ee, 2, '.', ''),
                    number_format($er, 2, '.', ''),
                    number_format($ee + $er, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export Pag-IBIG HDMF MCRF Remittance Schedule CSV
     */
    public function exportPagIbigRemittance(Request $request, string $cutoff): StreamedResponse
    {
        $computations = SalaryComputation::with('employee')
            ->where('cutoff_period', $cutoff)
            ->get();

        $filename = "PagIBIG_MCRF_Remittance_Schedule_{$cutoff}.csv";

        return response()->streamDownload(function () use ($computations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'PAGIBIG_MID',
                'EMPLOYEE_NAME',
                'MONTHLY_COMPENSATION',
                'EMPLOYEE_SHARE',
                'EMPLOYER_SHARE',
                'TOTAL_HDMF_REMITTANCE',
            ]);

            foreach ($computations as $c) {
                $emp = $c->employee;
                $ee = (float) $c->pagibig_deduction;
                $er = (float) ($c->pagibig_employer ?? $ee);

                fputcsv($handle, [
                    '0000-0000-0000',
                    strtoupper(($emp?->first_name ?? '') . ' ' . ($emp?->last_name ?? '')),
                    number_format((float)$c->gross_pay * 2, 2, '.', ''),
                    number_format($ee, 2, '.', ''),
                    number_format($er, 2, '.', ''),
                    number_format($ee + $er, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Update Employee Disbursement Channel (Security Bank vs Physical Cash)
     */
    public function updatePaymentMode(UpdatePaymentChannelRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($employee, $validated) {
            $employee->update([
                'payment_mode' => $validated['payment_mode'],
                'bank_name' => $validated['payment_mode'] === 'bank' ? ($validated['bank_name'] ?? 'Security Bank Corporation') : null,
                'bank_account_number' => $validated['payment_mode'] === 'bank' ? ($validated['bank_account_number'] ?? null) : null,
            ]);
        });

        $channelLabel = $validated['payment_mode'] === 'bank' ? 'Security Bank Corporation' : 'Physical Cash (Over-the-Counter)';

        return redirect()->route('payroll.payment-modes')
            ->with('status', "Disbursement channel for {$employee->first_name} {$employee->last_name} successfully updated to {$channelLabel}.");
    }

    /**
     * Export Official Security Bank Corporation (SBC) Bulk Disbursement File
     */
    public function exportSecurityBankFile(Request $request, string $cutoff): StreamedResponse
    {
        $computations = SalaryComputation::with('employee')
            ->where('cutoff_period', $cutoff)
            ->get();

        $csv = app(SecurityBankExportService::class)->generateCsv($computations, $cutoff);
        $filename = "SBC_Payroll_Disbursement_{$cutoff}.csv";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
