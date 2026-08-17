<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MedicalAssistanceService
{
    public function __construct(
        protected ClaimTaxabilityService $taxabilityService
    ) {}

    /**
     * File an internal employee medical assistance claim and apply the BIR TRAIN Law PHP 10,000 de minimis cap
     *
     * @param array<string, mixed> $data
     */
    public function fileMedicalClaim(array $data, ?UploadedFile $attachment = null): Claim
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $amount = (float) $data['amount'];
        $year = ! empty($data['expense_date'])
            ? (int) Carbon::parse($data['expense_date'])->year
            : (int) Carbon::now()->year;

        $category = ClaimCategory::where('code', 'CAT-MED')->first()
            ?: ClaimCategory::firstOrCreate(
                ['code' => 'CAT-MED'],
                [
                    'name' => 'Medical Assistance',
                    'type' => 'reimbursement',
                    'tax_classification' => 'de_minimis',
                    'color_tag' => 'emerald',
                    'max_amount' => 15000.00,
                    'de_minimis_annual_cap' => 10000.00,
                    'is_active' => true,
                    'applicable_to' => 'all',
                    'description' => 'Out-of-pocket medical, dental, and prescription expenses (PHP 10k/yr de minimis cap).',
                ]
            );

        // Classify tax breakdown against PHP 10,000 annual ceiling
        $taxBreakdown = $this->taxabilityService->classifyClaim($category, $amount, $employee, $year);

        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store('receipts/claims', 'public');
        }

        return DB::transaction(function () use ($data, $employee, $category, $amount, $taxBreakdown, $attachmentPath) {
            $receiptNo = ! empty($data['receipt_number'])
                ? strtoupper(trim((string) $data['receipt_number']))
                : ('MED-' . strtoupper(uniqid()));

            $claim = Claim::create([
                'employee_id' => $employee->id,
                'category_id' => $category->id,
                'category' => $category->name,
                'type' => 'expense',
                'expense_subtype' => 'medical',
                'receipt_number' => $receiptNo,
                'merchant_name' => $data['merchant_name'] ?? 'Medical Provider / Pharmacy',
                'merchant_tin' => $data['merchant_tin'] ?? null,
                'medical_condition' => $data['medical_condition'] ?? 'Outpatient Medical Care',
                'amount' => $amount,
                'non_taxable_amount' => $taxBreakdown['non_taxable_amount'],
                'taxable_amount' => $taxBreakdown['taxable_amount'],
                'tax_classification' => 'de_minimis',
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'cutoff_period' => $data['cutoff_period'] ?? '2026-07-01_15',
                'description' => $data['description'] ?? sprintf(
                    'Medical assistance for %s. Non-Taxable: PHP %s, Taxable: PHP %s. %s',
                    $data['medical_condition'] ?? 'medical treatment',
                    number_format($taxBreakdown['non_taxable_amount'], 2),
                    number_format($taxBreakdown['taxable_amount'], 2),
                    $taxBreakdown['formula_explanation']
                ),
                'attachment_path' => $attachmentPath,
                'approval_status' => 'pending_hr',
                'status' => 'pending',
                'hr_remarks' => $taxBreakdown['exceeds_cap']
                    ? 'Medical claim exceeds annual de minimis limit; excess portion split into taxable compensation.'
                    : 'Medical claim verified within annual de minimis exemption ceiling.',
            ]);

            PayrollAuditTrail::create([
                'action' => 'MEDICAL_ASSISTANCE_CLAIM_FILED',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => $employee->first_name . ' ' . $employee->last_name,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => [],
                'new_values' => [
                    'amount' => $amount,
                    'non_taxable' => $taxBreakdown['non_taxable_amount'],
                    'taxable' => $taxBreakdown['taxable_amount'],
                    'exceeds_cap' => $taxBreakdown['exceeds_cap'],
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Retrieve the running YTD medical claims utilization for an employee
     *
     * @return array{
     *     employee_id: int,
     *     employee_name: string,
     *     annual_cap: float,
     *     ytd_utilized: float,
     *     ytd_remaining: float,
     *     utilization_pct: float
     * }
     */
    public function getEmployeeMedicalYtdStatus(Employee $employee, ?int $year = null): array
    {
        $year = $year ?? (int) Carbon::now()->year;
        $category = ClaimCategory::where('code', 'CAT-MED')->first();
        $annualCap = (float) ($category?->de_minimis_annual_cap ?: ClaimTaxabilityService::DEFAULT_MEDICAL_DE_MINIMIS_CAP);

        $utilized = (float) Claim::where('employee_id', $employee->id)
            ->where(function ($q) use ($category) {
                if ($category) {
                    $q->where('category_id', $category->id);
                }
                $q->orWhere('expense_subtype', 'medical');
            })
            ->whereIn('approval_status', ['approved', 'payroll_queued', 'paid'])
            ->whereYear('created_at', $year)
            ->sum('non_taxable_amount');

        $remaining = max(0.00, round($annualCap - $utilized, 2));
        $pct = $annualCap > 0 ? min(100.0, round(($utilized / $annualCap) * 100, 1)) : 0.0;

        return [
            'employee_id' => $employee->id,
            'employee_name' => trim($employee->first_name . ' ' . $employee->last_name),
            'annual_cap' => $annualCap,
            'ytd_utilized' => $utilized,
            'ytd_remaining' => $remaining,
            'utilization_pct' => $pct,
        ];
    }
}
