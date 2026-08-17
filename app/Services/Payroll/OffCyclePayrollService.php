<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Enums\OffCycleRunType;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanAmortizationLog;
use App\Models\OffCyclePayroll;
use App\Models\OffCyclePayrollItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OffCyclePayrollService
{
    public function __construct(
        protected FinalPaySettlementService $finalPayService,
        protected ThirteenthMonthService $thirteenthMonthService
    ) {}

    /**
     * Create a Final Pay Settlement Batch
     *
     * @param array{
     *     employee_id: int,
     *     separation_date: string,
     *     payout_date: string,
     *     unpaid_days?: float,
     *     unused_leaves?: float,
     *     other_deductions?: float,
     *     reimbursements?: float,
     *     notes?: string
     * } $data
     */
    public function createFinalPayRun(array $data): OffCyclePayroll
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::findOrFail($data['employee_id']);
            $separationDate = Carbon::parse($data['separation_date']);
            $payoutDate = Carbon::parse($data['payout_date'] ?? now());

            $unpaidDays = (float) ($data['unpaid_days'] ?? 0);
            $unusedLeaves = (float) ($data['unused_leaves'] ?? 0);
            $otherDeductions = (float) ($data['other_deductions'] ?? 0);
            $reimbursements = (float) ($data['reimbursements'] ?? 0);

            $calc = $this->finalPayService->computeFinalPay(
                $employee,
                $separationDate,
                $unpaidDays,
                $unusedLeaves,
                $otherDeductions,
                $reimbursements
            );

            $runCount = OffCyclePayroll::count() + 1;
            $runNumber = sprintf('OFF-%s-%03d', date('Y'), $runCount);

            $offCycle = OffCyclePayroll::create([
                'run_number' => $runNumber,
                'run_type' => OffCycleRunType::FINAL_PAY,
                'title' => "Final Pay & Separation Settlement - {$employee->first_name} {$employee->last_name}",
                'payout_date' => $payoutDate,
                'status' => 'draft',
                'total_gross' => $calc['gross_amount'],
                'total_deductions' => $calc['total_deductions'],
                'total_net_pay' => $calc['net_settlement_pay'],
                'notes' => $data['notes'] ?? "Separation date: {$calc['separation_date']}",
            ]);

            OffCyclePayrollItem::create([
                'off_cycle_payroll_id' => $offCycle->id,
                'employee_id' => $employee->id,
                'basic_pay_earned' => $calc['basic_pay_earned'],
                'pro_rated_13th_month' => $calc['pro_rated_13th_month'],
                'leave_conversion_pay' => $calc['leave_conversion_pay'],
                'bonuses_differentials' => 0.00,
                'reimbursements' => $calc['reimbursements'],
                'gross_amount' => $calc['gross_amount'],
                'withholding_tax' => $calc['withholding_tax'],
                'loan_deduction' => $calc['loan_deduction'],
                'other_deductions' => $calc['other_deductions'],
                'total_deductions' => $calc['total_deductions'],
                'net_settlement_pay' => $calc['net_settlement_pay'],
                'computation_breakdown' => $calc['breakdown'],
                'status' => 'pending',
            ]);

            return $offCycle;
        });
    }

    /**
     * Create a Special Bonus or Salary Differential Off-Cycle Run
     *
     * @param array{
     *     run_type: string,
     *     title: string,
     *     payout_date: string,
     *     items: array<int, array{employee_id: int, amount: float, notes?: string}>,
     *     notes?: string
     * } $data
     */
    public function createSpecialRun(array $data): OffCyclePayroll
    {
        return DB::transaction(function () use ($data) {
            $runCount = OffCyclePayroll::count() + 1;
            $runNumber = sprintf('OFF-%s-%03d', date('Y'), $runCount);
            $payoutDate = Carbon::parse($data['payout_date']);
            $runType = OffCycleRunType::from($data['run_type']);

            $totalGross = 0.00;
            $totalNet = 0.00;

            $offCycle = OffCyclePayroll::create([
                'run_number' => $runNumber,
                'run_type' => $runType,
                'title' => $data['title'],
                'payout_date' => $payoutDate,
                'status' => 'draft',
                'total_gross' => 0.00,
                'total_deductions' => 0.00,
                'total_net_pay' => 0.00,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $amount = (float) $item['amount'];
                $totalGross += $amount;
                $totalNet += $amount;

                OffCyclePayrollItem::create([
                    'off_cycle_payroll_id' => $offCycle->id,
                    'employee_id' => $item['employee_id'],
                    'bonuses_differentials' => $amount,
                    'gross_amount' => $amount,
                    'total_deductions' => 0.00,
                    'net_settlement_pay' => $amount,
                    'status' => 'pending',
                ]);
            }

            $offCycle->update([
                'total_gross' => $totalGross,
                'total_net_pay' => $totalNet,
            ]);

            return $offCycle;
        });
    }

    /**
     * Approve Off-Cycle Payroll Batch
     */
    public function approveRun(OffCyclePayroll $offCycle): void
    {
        $offCycle->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $offCycle->items()->update(['status' => 'approved']);
    }

    /**
     * Release and Disburse Off-Cycle Payroll Batch
     */
    public function releaseRun(OffCyclePayroll $offCycle): void
    {
        DB::transaction(function () use ($offCycle) {
            $offCycle->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            $offCycle->items()->update(['status' => 'released']);

            // If final pay, offset loans and update employee status
            if ($offCycle->run_type === OffCycleRunType::FINAL_PAY) {
                foreach ($offCycle->items as $item) {
                    $employee = $item->employee;
                    if (! $employee) continue;

                    $remainingLoanDeduction = (float) $item->loan_deduction;
                    if ($remainingLoanDeduction > 0) {
                        $activeLoans = EmployeeLoan::where('employee_id', $employee->id)
                            ->where('status', 'active')
                            ->orderBy('id')
                            ->get();

                        foreach ($activeLoans as $loan) {
                            if ($remainingLoanDeduction <= 0) break;

                            $deductFromThisLoan = min($remainingLoanDeduction, (float) $loan->remaining_balance);
                            $newBalance = max(0.00, (float) $loan->remaining_balance - $deductFromThisLoan);
                            $newTotalPaid = (float) $loan->total_paid + $deductFromThisLoan;
                            $newStatus = $newBalance <= 0.001 ? 'fully_paid' : 'active';

                            $loan->update([
                                'remaining_balance' => $newBalance,
                                'total_paid' => $newTotalPaid,
                                'status' => $newStatus,
                            ]);

                            LoanAmortizationLog::create([
                                'employee_loan_id' => $loan->id,
                                'salary_computation_id' => null,
                                'cutoff_period' => "FINAL_PAY_{$offCycle->run_number}",
                                'amount_deducted' => $deductFromThisLoan,
                                'remaining_balance_after' => $newBalance,
                                'deducted_at' => now(),
                            ]);

                            $remainingLoanDeduction -= $deductFromThisLoan;
                        }
                    }

                    // Update employee employment status to resigned/separated
                    $employee->update([
                        'employment_status' => 'resigned',
                        'is_active' => false,
                    ]);
                }
            }
        });
    }
}
