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

        // Accumulated driver deductions from payroll or ledger
        $payrollDeductions = (float) SalaryComputation::sum('hmo_insurance_deduction');
        $ledgerDeductions = (float) DriverPoolLedger::where('entry_type', 'driver_contribution')->sum('amount');
        $totalDriverContributions = max($payrollDeductions, $ledgerDeductions);

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
     * Stream CSV export of complete Driver Insurance Pool Fund Accounting Ledger
     */
    public function exportPoolLedgerCsv(): StreamedResponse
    {
        $entries = DriverPoolLedger::with('employee')->latest()->get();
        $filename = 'driver_insurance_pool_ledger_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($entries) {
            $file = fopen('php://output', 'w');
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

        return response()->stream($callback, 200, $headers);
    }
}
