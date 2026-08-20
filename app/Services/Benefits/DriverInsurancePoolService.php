<?php

declare(strict_types=1);

namespace App\Services\Benefits;

use App\Models\AccidentClaim;
use App\Models\CompanySetting;
use App\Models\DriverPoolLedger;
use App\Models\Employee;
use App\Models\SalaryComputation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriverInsurancePoolService
{
    /**
     * Retrieve Comprehensive Driver Accident Insurance Pool Accounting Summary (known.md §8.7 & §8.8)
     *
     * @return array<string, mixed>
     */
    public function getPoolSummary(): array
    {
        $contributionRate = (float) CompanySetting::getValue('driver_benefit_contribution_rate', 0.03);
        $companyMatchPct = (float) CompanySetting::getValue('driver_pool_company_match_pct', 50.0);

        // Accumulated driver contributions from pool ledger
        $totalDriverContributions = (float) DriverPoolLedger::where('entry_type', 'driver_contribution')->sum('amount');

        // Company matching subsidy
        $ledgerSubsidies = (float) DriverPoolLedger::where('entry_type', 'company_subsidy_match')->sum('amount');
        $expectedSubsidies = round($totalDriverContributions * ($companyMatchPct / 100.0), 2);
        $totalCompanySubsidies = max($ledgerSubsidies, $expectedSubsidies);

        $totalFundInflow = $totalDriverContributions + $totalCompanySubsidies;

        // Total disbursed claims
        $totalDisbursed = (float) AccidentClaim::where('workflow_status', 'approved')->sum('approved_amount');

        // Pending liabilities pipeline
        $pendingPipeline = (float) AccidentClaim::whereIn('workflow_status', ['pending_hr', 'pending_admin', 'pending_finance'])->sum('bill_amount');

        $pendingCount = AccidentClaim::whereIn('workflow_status', ['pending_hr', 'pending_admin', 'pending_finance'])->count();
        $approvedCount = AccidentClaim::where('workflow_status', 'approved')->count();
        $returnedCount = AccidentClaim::where('workflow_status', 'returned')->count();

        $netLiquidBalance = max(0.00, $totalFundInflow - $totalDisbursed);
        $activeDriversCount = Employee::where('position', 'like', '%Driver%')->where('employment_status', '!=', 'terminated')->count();

        return [
            'total_driver_contributions' => $totalDriverContributions,
            'total_company_subsidies' => $totalCompanySubsidies,
            'total_fund_inflow' => $totalFundInflow,
            'total_disbursed' => $totalDisbursed,
            'pending_pipeline' => $pendingPipeline,
            'net_liquid_balance' => $netLiquidBalance,
            'contribution_rate_pct' => $contributionRate * 100.0,
            'company_match_pct' => $companyMatchPct,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'returned_count' => $returnedCount,
            'total_claims' => AccidentClaim::count(),
            'active_drivers_count' => $activeDriversCount,
        ];
    }

    /**
     * File a new driver accident claim with evidence files (known.md §8.7 Step 1)
     *
     * @param array<string, mixed> $data
     */
    public function fileClaim(
        int $employeeId,
        array $data,
        ?UploadedFile $policeReport = null,
        ?UploadedFile $medicalReceipt = null,
        ?UploadedFile $incidentPhoto = null
    ): AccidentClaim {
        return DB::transaction(function () use ($employeeId, $data, $policeReport, $medicalReceipt, $incidentPhoto) {
            $employee = Employee::findOrFail($employeeId);

            $policeReportPath = null;
            if ($policeReport) {
                $policeReportPath = $policeReport->store('uploads/claims/police_reports', 'public');
            }

            $medicalReceiptPath = null;
            if ($medicalReceipt) {
                $medicalReceiptPath = $medicalReceipt->store('uploads/claims/medical_receipts', 'public');
            }

            $incidentPhotoPath = null;
            if ($incidentPhoto) {
                $incidentPhotoPath = $incidentPhoto->store('uploads/claims/photos', 'public');
            }

            $incidentNumber = 'ACCD-' . now()->format('Y') . '-' . str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);

            return AccidentClaim::create([
                'employee_id' => $employee->id,
                'incident_number' => $incidentNumber,
                'incident_type' => $data['incident_type'] ?? 'Work Injury',
                'incident_date' => $data['incident_date'] ?? now()->toDateString(),
                'bill_amount' => (float) $data['bill_amount'],
                'approved_amount' => null,
                'description' => $data['description'],
                'vehicle_plate_number' => $data['vehicle_plate_number'] ?? null,
                'trip_id' => $data['trip_id'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'police_report_path' => $policeReportPath,
                'medical_receipt_path' => $medicalReceiptPath,
                'incident_photo_path' => $incidentPhotoPath,
                'documents_uploaded' => (bool) ($policeReportPath || $medicalReceiptPath || $incidentPhotoPath),
                'hr_status' => 'pending',
                'admin_status' => 'pending',
                'finance_status' => 'pending',
                'workflow_status' => 'pending_hr',
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Stage 1: HR Reviews & Validates Duty Status and Receipts (known.md §8.7 Step 2)
     */
    public function approveHr(AccidentClaim $claim, float $approvedAmount, ?string $remarks = null): AccidentClaim
    {
        $claim->update([
            'approved_amount' => $approvedAmount,
            'hr_status' => 'approved',
            'hr_remarks' => $remarks ?? 'Verified active trip status, driver diagnosis, and medical receipts.',
            'hr_reviewed_at' => now(),
            'workflow_status' => 'pending_admin',
        ]);

        return $claim;
    }

    /**
     * Stage 2: Fleet Admin / Operations Verifies Road Incident (known.md §8.7 Step 3)
     */
    public function approveAdmin(AccidentClaim $claim, ?string $remarks = null): AccidentClaim
    {
        $claim->update([
            'admin_status' => 'approved',
            'admin_remarks' => $remarks ?? 'Validated vehicle damage assessment and police blotter record.',
            'admin_reviewed_at' => now(),
            'workflow_status' => 'pending_finance',
        ]);

        return $claim;
    }

    /**
     * Stage 3: Finance Team 5 Disburses Payout from Driver Pool (known.md §8.7 Step 4 & 5)
     */
    public function approveFinance(AccidentClaim $claim, ?string $remarks = null): AccidentClaim
    {
        return DB::transaction(function () use ($claim, $remarks) {
            $claim->update([
                'finance_status' => 'approved',
                'finance_remarks' => $remarks ?? 'Accident reimbursement disbursement approved and released from Driver Pool.',
                'finance_reviewed_at' => now(),
                'workflow_status' => 'approved',
                'status' => 'paid',
            ]);

            $payoutAmount = (float) ($claim->approved_amount ?: $claim->bill_amount);

            // Record transaction entry into Driver Pool Ledger
            $summary = $this->getPoolSummary();
            $newBalance = max(0.00, $summary['net_liquid_balance'] - $payoutAmount);

            DriverPoolLedger::create([
                'employee_id' => $claim->employee_id,
                'entry_type' => 'claim_disbursement',
                'amount' => -$payoutAmount,
                'running_balance' => $newBalance,
                'reference_code' => $claim->incident_number,
                'description' => "Disbursement for {$claim->incident_type} ({$claim->incident_number})",
            ]);

            return $claim;
        });
    }

    /**
     * Return claim back for revision / additional documents
     */
    public function returnClaim(
        AccidentClaim $claim,
        string $reason,
        string $targetStage = 'pending_hr',
        ?string $returnedBy = 'HR Team 4'
    ): AccidentClaim {
        $claim->update([
            'workflow_status' => 'returned',
            'return_reason' => $reason,
            'hr_remarks' => $reason,
            'returned_by' => $returnedBy,
            'returned_at' => now(),
            'status' => 'returned',
        ]);

        return $claim;
    }

    /**
     * Update Driver Accident Insurance Pool Contribution Rate & Company Match Settings
     */
    public function updatePoolContributionRate(float $ratePct, float $companyMatchPct): void
    {
        $decimalRate = $ratePct / 100.0;

        CompanySetting::updateOrCreate(
            ['key' => 'driver_benefit_contribution_rate'],
            ['value' => (string) $decimalRate, 'description' => 'Driver Accident Insurance Pool Contribution Rate']
        );

        CompanySetting::updateOrCreate(
            ['key' => 'driver_pool_company_match_pct'],
            ['value' => (string) $companyMatchPct, 'description' => 'Company Matching Subsidy Percentage for Driver Pool']
        );
    }

    /**
     * Get paginated ledger transactions for Driver Accident Insurance Pool
     */
    public function getPoolLedger(?int $employeeId = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = DriverPoolLedger::with('employee')->latest();

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Record automated Driver Accident Insurance Pool contribution upon payroll release.
     */
    public function recordPayrollContribution(SalaryComputation $computation, ?float $overrideRate = null): ?DriverPoolLedger
    {
        $employee = $computation->employee ?? Employee::find($computation->employee_id);
        if (! $employee) {
            return null;
        }

        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver') || ((float) ($computation->trip_pay ?? 0.0) > 0);
        if (! $isDriver) {
            return null;
        }

        $refCode = "CONTR-{$computation->cutoff_period}-{$computation->employee_id}";
        $existing = DriverPoolLedger::where('reference_code', $refCode)->first();
        if ($existing) {
            return $existing;
        }

        $rate = $overrideRate !== null
            ? $overrideRate
            : (float) CompanySetting::getValue('driver_benefit_contribution_rate', 0.03);

        $tripAmount = (float) (($computation->trip_earnings ?? 0) > 0 ? $computation->trip_earnings : ($computation->trip_pay ?? 0));
        $baseAmount = $tripAmount > 0 ? $tripAmount : (float) $computation->gross_pay;
        $contributionAmount = round($baseAmount * $rate, 2);

        if ($contributionAmount <= 0.0) {
            return null;
        }

        return DB::transaction(function () use ($computation, $employee, $refCode, $contributionAmount, $rate) {
            $latestBalance = (float) (DriverPoolLedger::latest('id')->value('running_balance') ?? 0.00);
            $newBalance = $latestBalance + $contributionAmount;
            $ratePct = round($rate * 100.0, 1);

            $driverEntry = DriverPoolLedger::create([
                'employee_id' => $employee->id,
                'entry_type' => 'driver_contribution',
                'reference_code' => $refCode,
                'amount' => $contributionAmount,
                'running_balance' => $newBalance,
                'description' => "Driver payroll contribution for cutoff [{$computation->cutoff_period}] ({$ratePct}% of earnings).",
            ]);

            // Company matching subsidy
            $matchPct = (float) CompanySetting::getValue('driver_pool_company_match_pct', 50.0);
            if ($matchPct > 0) {
                $matchAmount = round($contributionAmount * ($matchPct / 100.0), 2);
                $matchBalance = $newBalance + $matchAmount;
                $matchRef = "MATCH-{$computation->cutoff_period}-{$computation->employee_id}";

                DriverPoolLedger::create([
                    'employee_id' => null,
                    'entry_type' => 'company_subsidy_match',
                    'reference_code' => $matchRef,
                    'amount' => $matchAmount,
                    'running_balance' => $matchBalance,
                    'description' => "TripWise {$matchPct}% matching subsidy for driver {$employee->first_name} {$employee->last_name} [{$computation->cutoff_period}].",
                ]);
            }

            return $driverEntry;
        });
    }

    /**
     * Retrieve complete contribution and claim history for an individual driver.
     */
    public function getDriverContributionHistory(Employee $employee): array
    {
        $contributions = DriverPoolLedger::where('employee_id', $employee->id)
            ->where('entry_type', 'driver_contribution')
            ->latest()
            ->get();

        $totalContributed = (float) $contributions->sum('amount');
        $matchPct = (float) CompanySetting::getValue('driver_pool_company_match_pct', 50.0);
        $totalCompanyMatch = round($totalContributed * ($matchPct / 100.0), 2);
        $totalPoolCredit = $totalContributed + $totalCompanyMatch;

        $claims = AccidentClaim::where('employee_id', $employee->id)->latest()->get();
        $totalClaimsDisbursed = (float) $claims->where('workflow_status', 'approved')->sum('approved_amount');

        return [
            'employee' => $employee,
            'total_contributed' => $totalContributed,
            'company_match_total' => $totalCompanyMatch,
            'total_pool_credit' => $totalPoolCredit,
            'claims_count' => $claims->count(),
            'claims_disbursed_total' => $totalClaimsDisbursed,
            'net_coverage_balance' => max(0.00, $totalPoolCredit - $totalClaimsDisbursed),
            'contributions' => $contributions,
            'claims' => $claims,
        ];
    }

    /**
     * Generate Periodic Financial Statement for Driver Accident Insurance Pool
     */
    public function generatePeriodicStatement(?string $startDate = null, ?string $endDate = null): array
    {
        $query = DriverPoolLedger::with('employee');
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $entries = $query->orderBy('id', 'asc')->get();

        $driverInflow = (float) $entries->where('entry_type', 'driver_contribution')->sum('amount');
        $companyMatchInflow = (float) $entries->where('entry_type', 'company_subsidy_match')->sum('amount');
        $totalInflows = $driverInflow + $companyMatchInflow;

        $claimQuery = AccidentClaim::with('employee');
        if ($startDate && $endDate) {
            $claimQuery->whereBetween('disbursed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        $disbursedClaims = $claimQuery->where('workflow_status', 'approved')->get();
        $totalDisbursements = (float) $disbursedClaims->sum('approved_amount');

        $pendingLiabilities = (float) AccidentClaim::whereIn('workflow_status', ['pending_hr', 'pending_admin', 'pending_finance'])->sum('bill_amount');
        $netReserveBalance = (float) (DriverPoolLedger::latest('id')->value('running_balance') ?? max(0.00, $totalInflows - $totalDisbursements));

        return [
            'start_date' => $startDate ?? 'Beginning of Operations',
            'end_date' => $endDate ?? now()->format('Y-m-d'),
            'driver_inflows' => $driverInflow,
            'company_match_inflows' => $companyMatchInflow,
            'total_inflows' => $totalInflows,
            'total_disbursements' => $totalDisbursements,
            'disbursed_claims_count' => $disbursedClaims->count(),
            'pending_liabilities' => $pendingLiabilities,
            'net_reserve_balance' => $netReserveBalance,
            'entries' => $entries,
            'disbursed_claims' => $disbursedClaims,
        ];
    }

    /**
     * Stream CSV export of complete Driver Insurance Pool Fund Accounting Ledger
     */
    public function exportPoolLedgerCsv(): StreamedResponse
    {
        $entries = DriverPoolLedger::with('employee')->latest()->get();
        $filename = 'driver_insurance_pool_ledger_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($entries) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($file, [
                'Transaction Date',
                'Reference Code',
                'Entry Type',
                'Driver Code',
                'Driver Name',
                'Description',
                'Amount (PHP)',
                'Running Fund Balance (PHP)',
            ]);

            foreach ($entries as $e) {
                fputcsv($file, [
                    $e->created_at->format('Y-m-d H:i:s'),
                    $e->reference_code,
                    $e->entry_type_label,
                    $e->employee?->employee_code ?? 'POOL-GEN',
                    $e->employee ? ($e->employee->first_name . ' ' . $e->employee->last_name) : 'TripWise Corporate Match',
                    $e->description,
                    number_format((float) $e->amount, 2, '.', ''),
                    number_format((float) $e->running_balance, 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    /**
     * Stream CSV export of Driver Insurance Pool Periodic Financial Statement
     */
    public function exportPeriodicStatementCsv(?string $startDate = null, ?string $endDate = null): StreamedResponse
    {
        $statement = $this->generatePeriodicStatement($startDate, $endDate);
        $filename = 'driver_insurance_pool_financial_statement_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($statement) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($file, ['DRIVER ACCIDENT INSURANCE POOL - PERIODIC FINANCIAL STATEMENT']);
            fputcsv($file, ['Reporting Period', $statement['start_date'] . ' to ' . $statement['end_date']]);
            fputcsv($file, ['Generated On', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            fputcsv($file, ['FINANCIAL METRIC', 'AMOUNT (PHP)']);
            fputcsv($file, ['Total Driver Inflows (Payroll Contributions)', number_format($statement['driver_inflows'], 2, '.', '')]);
            fputcsv($file, ['Total Company Matching Subsidies', number_format($statement['company_match_inflows'], 2, '.', '')]);
            fputcsv($file, ['TOTAL FUND INFLOWS', number_format($statement['total_inflows'], 2, '.', '')]);
            fputcsv($file, ['Total Accident Claim Outflows Disbursed', number_format($statement['total_disbursements'], 2, '.', '')]);
            fputcsv($file, ['Disbursed Claims Count', (string) $statement['disbursed_claims_count']]);
            fputcsv($file, ['Pending Claim Liabilities (In Review)', number_format($statement['pending_liabilities'], 2, '.', '')]);
            fputcsv($file, ['NET ENDING LIQUID RESERVE', number_format($statement['net_reserve_balance'], 2, '.', '')]);
            fputcsv($file, []);

            fputcsv($file, ['DISBURSED ACCIDENT CLAIMS BREAKDOWN']);
            fputcsv($file, ['Incident Number', 'Driver Code', 'Driver Name', 'Incident Date', 'Disbursed Date', 'Billed Amount (PHP)', 'Disbursed Amount (PHP)']);

            foreach ($statement['disbursed_claims'] as $claim) {
                fputcsv($file, [
                    $claim->incident_number,
                    $claim->employee?->employee_code ?? 'N/A',
                    $claim->employee ? ($claim->employee->first_name . ' ' . $claim->employee->last_name) : 'N/A',
                    $claim->incident_date ? $claim->incident_date->format('Y-m-d') : 'N/A',
                    $claim->disbursed_at ? $claim->disbursed_at->format('Y-m-d H:i:s') : 'N/A',
                    number_format((float) $claim->bill_amount, 2, '.', ''),
                    number_format((float) $claim->approved_amount, 2, '.', ''),
                ]);
            }

            fclose($file);
        }, $filename, $headers);
    }
}
