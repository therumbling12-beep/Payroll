<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MaternityBenefitService
{
    public const SSS_MAX_MSC = 30000.00;

    public const MATERNITY_TYPES = [
        'normal_caesarean' => ['days' => 105, 'label' => 'Live Childbirth (Normal / Caesarean - 105 Days)'],
        'solo_parent' => ['days' => 120, 'label' => 'Solo Parent Female Employee (RA 8972 - 120 Days)'],
        'miscarriage' => ['days' => 60, 'label' => 'Miscarriage / Emergency Termination (60 Days)'],
    ];

    /**
     * Get maximum SSS Monthly Salary Credit (MSC) ceiling from company settings or statutory default
     */
    public function getSssMaxMsc(): float
    {
        return (float) CompanySetting::getValue('sss_max_msc', self::SSS_MAX_MSC);
    }

    /**
     * Compute statutory maternity benefit components under RA 11210 (Expanded Maternity Leave Act)
     *
     * @return array{
     *     employee_id: int,
     *     employee_name: string,
     *     monthly_rate: float,
     *     daily_rate: float,
     *     maternity_type: string,
     *     maternity_type_label: string,
     *     leave_days: int,
     *     sss_msc: float,
     *     sss_daily_rate: float,
     *     sss_maternity_share: float,
     *     full_basic_pay: float,
     *     company_salary_differential: float,
     *     total_advance_amount: float,
     *     formula_explanation: string
     * }
     */
    public function computeMaternityBenefit(Employee $employee, string $maternityType = 'normal_caesarean'): array
    {
        $typeDef = self::MATERNITY_TYPES[$maternityType] ?? self::MATERNITY_TYPES['normal_caesarean'];
        $leaveDays = $typeDef['days'];

        $monthlyRate = (float) ($employee->monthly_rate ?: 25000.00);
        $dailyRate = (float) ($employee->daily_rate ?: round($monthlyRate / 26, 2));

        // SSS 6-Highest Monthly Salary Credits (MSC) calculation
        $sssMsc = min($this->getSssMaxMsc(), $monthlyRate);
        $sssDailyRate = round((6 * $sssMsc) / 180, 2);
        $sssShare = round($sssDailyRate * $leaveDays, 2);

        // Full Basic Salary for the leave duration
        $fullBasicPay = round($dailyRate * $leaveDays, 2);

        // Mandatory Company Salary Differential under RA 11210
        $companyDifferential = max(0.00, round($fullBasicPay - $sssShare, 2));
        $totalAdvance = round($sssShare + $companyDifferential, 2);

        $explanation = sprintf(
            'RA 11210 Maternity Benefit for %d days (%s): SSS Maternity Advance is PHP %s (based on SSS MSC PHP %s). Company Salary Differential is PHP %s (Full pay: PHP %s at PHP %s/day). Total 100%% employer advance: PHP %s.',
            $leaveDays,
            $typeDef['label'],
            number_format($sssShare, 2),
            number_format($sssMsc, 2),
            number_format($companyDifferential, 2),
            number_format($fullBasicPay, 2),
            number_format($dailyRate, 2),
            number_format($totalAdvance, 2)
        );

        return [
            'employee_id' => $employee->id,
            'employee_name' => trim($employee->first_name . ' ' . $employee->last_name),
            'monthly_rate' => $monthlyRate,
            'daily_rate' => $dailyRate,
            'maternity_type' => $maternityType,
            'maternity_type_label' => $typeDef['label'],
            'leave_days' => $leaveDays,
            'sss_msc' => $sssMsc,
            'sss_daily_rate' => $sssDailyRate,
            'sss_maternity_share' => $sssShare,
            'full_basic_pay' => $fullBasicPay,
            'company_salary_differential' => $companyDifferential,
            'total_advance_amount' => $totalAdvance,
            'formula_explanation' => $explanation,
        ];
    }

    /**
     * File a new Maternity Leave claim and initialize SSS advance records
     *
     * @param array<string, mixed> $data
     */
    public function fileMaternityClaim(array $data, ?UploadedFile $attachment = null): Claim
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $maternityType = $data['maternity_type'] ?? 'normal_caesarean';

        $calc = $this->computeMaternityBenefit($employee, $maternityType);

        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store('receipts/claims', 'public');
        }

        $category = ClaimCategory::where('code', 'CAT-MAT')->first()
            ?: ClaimCategory::firstOrCreate(
                ['code' => 'CAT-MAT'],
                [
                    'name' => 'Maternity Leave Benefit',
                    'type' => 'maternity',
                    'tax_classification' => 'non_taxable',
                    'color_tag' => 'pink',
                    'max_amount' => 150000.00,
                    'is_active' => true,
                    'applicable_to' => 'regular',
                    'description' => '105-day statutory maternity benefit advance and company differential.',
                ]
            );

        return DB::transaction(function () use ($data, $employee, $category, $calc, $maternityType, $attachmentPath) {
            $receiptNo = ! empty($data['receipt_number'])
                ? strtoupper(trim((string) $data['receipt_number']))
                : sprintf('MAT-RA11210-%s-%04d', $employee->employee_code, $employee->id);

            $claim = Claim::create([
                'employee_id' => $employee->id,
                'category_id' => $category->id,
                'category' => $category->name,
                'type' => 'maternity',
                'expense_subtype' => 'maternity',
                'receipt_number' => $receiptNo,
                'amount' => $calc['total_advance_amount'],
                'non_taxable_amount' => $calc['total_advance_amount'], // 100% Tax-Exempt Statutory Benefit
                'taxable_amount' => 0.00,
                'tax_classification' => 'non_taxable',
                'sss_maternity_share' => $calc['sss_maternity_share'],
                'company_maternity_topup' => $calc['company_salary_differential'],
                'maternity_type' => $maternityType,
                'maternity_leave_days' => $calc['leave_days'],
                'sss_reimbursement_status' => 'advanced_to_employee',
                'doctor_license_number' => $data['doctor_license_number'] ?? null,
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'cutoff_period' => $data['cutoff_period'] ?? '2026-07-01_15',
                'description' => $data['description'] ?? sprintf(
                    'RA 11210 Maternity Advance (%s). %s',
                    $calc['maternity_type_label'],
                    $calc['formula_explanation']
                ),
                'attachment_path' => $attachmentPath,
                'approval_status' => 'pending_hr',
                'status' => 'pending',
                'hr_remarks' => 'Maternity Leave Filed with OB-GYN medical certificate / SSS Mat-1 notice.',
            ]);

            PayrollAuditTrail::create([
                'action' => 'MATERNITY_CLAIM_FILED',
                'model_type' => Claim::class,
                'model_id' => $claim->id,
                'user_name' => $employee->first_name . ' ' . $employee->last_name,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'old_values' => [],
                'new_values' => [
                    'maternity_type' => $maternityType,
                    'leave_days' => $calc['leave_days'],
                    'sss_share' => $calc['sss_maternity_share'],
                    'company_differential' => $calc['company_salary_differential'],
                    'total_advance' => $calc['total_advance_amount'],
                ],
            ]);

            return $claim;
        });
    }

    /**
     * Update the employer SSS reimbursement recovery lifecycle status
     */
    public function updateSssReimbursementStatus(
        Claim $claim,
        string $status,
        ?string $referenceNumber = null,
        ?string $reimbursementDate = null
    ): Claim {
        $updates = ['sss_reimbursement_status' => $status];

        if ($referenceNumber) {
            $updates['sss_reference_number'] = strtoupper(trim($referenceNumber));
        }

        if ($reimbursementDate) {
            $updates['sss_reimbursement_date'] = $reimbursementDate;
        }

        $claim->update($updates);

        PayrollAuditTrail::create([
            'action' => 'MATERNITY_SSS_STATUS_UPDATED',
            'model_type' => Claim::class,
            'model_id' => $claim->id,
            'user_name' => 'HR Benefits Specialist',
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'old_values' => ['status' => $claim->getOriginal('sss_reimbursement_status')],
            'new_values' => $updates,
        ]);

        return $claim;
    }

    /**
     * Get summary metrics for the maternity leave dashboard
     *
     * @return array{
     *     total_disbursed: float,
     *     sss_share_total: float,
     *     company_differential_total: float,
     *     pending_sss_reimbursement_total: float,
     *     claims_count: int,
     *     reimbursed_count: int
     * }
     */
    public function getMaternitySummaryStats(): array
    {
        $all = Claim::where('type', 'maternity')->get();

        return [
            'total_disbursed' => (float) $all->sum('amount'),
            'sss_share_total' => (float) $all->sum('sss_maternity_share'),
            'company_differential_total' => (float) $all->sum('company_maternity_topup'),
            'pending_sss_reimbursement_total' => (float) $all->whereIn('sss_reimbursement_status', ['advanced_to_employee', 'submitted_to_sss'])->sum('sss_maternity_share'),
            'claims_count' => $all->count(),
            'reimbursed_count' => $all->where('sss_reimbursement_status', 'reimbursed_by_sss')->count(),
        ];
    }
}
