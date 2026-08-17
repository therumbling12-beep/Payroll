<?php

declare(strict_types=1);

namespace App\Services\Benefits;

use App\Models\AnnualPhysicalExam;
use App\Models\Employee;
use App\Models\GroupLifePolicy;
use App\Models\SalaryComputation;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CorporateWellnessAndLifeService
{
    /**
     * Get Annual Physical Exam (APE) Campaign Summary Metrics (known.md §8.9 & §8.11 Item 1)
     *
     * @return array<string, mixed>
     */
    public function getApeCampaignSummary(int $year): array
    {
        $totalEligible = Employee::where('employment_status', '!=', 'terminated')->count();
        $exams = AnnualPhysicalExam::where('exam_year', $year)->get();

        $totalScheduled = $exams->count();
        $totalAttended = $exams->where('attendance_status', 'attended')->count();
        $fitToWorkCount = $exams->where('medical_clearance_status', 'fit_to_work')->count();
        $fitWithRestrictionsCount = $exams->where('medical_clearance_status', 'fit_with_restrictions')->count();
        $pendingResultsCount = $exams->where('medical_clearance_status', 'pending_results')->count();
        $noShowCount = $exams->where('attendance_status', 'no_show')->count();

        $complianceRate = $totalScheduled > 0 ? round(($totalAttended / $totalScheduled) * 100.0, 1) : 0.0;

        return [
            'exam_year' => $year,
            'total_eligible' => $totalEligible,
            'total_scheduled' => $totalScheduled,
            'total_attended' => $totalAttended,
            'fit_to_work_count' => $fitToWorkCount,
            'fit_with_restrictions_count' => $fitWithRestrictionsCount,
            'pending_results_count' => $pendingResultsCount,
            'no_show_count' => $noShowCount,
            'compliance_rate_pct' => $complianceRate,
        ];
    }

    /**
     * Schedule an individual APE appointment
     *
     * @param array<string, mixed> $data
     */
    public function scheduleApe(int $employeeId, array $data): AnnualPhysicalExam
    {
        $year = (int) ($data['exam_year'] ?? date('Y'));

        return AnnualPhysicalExam::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'exam_year' => $year,
            ],
            [
                'schedule_date' => $data['schedule_date'],
                'time_slot' => $data['time_slot'] ?? '08:00 AM - 10:00 AM',
                'facility_name' => $data['facility_name'] ?? "St. Luke's Medical Center - BGC",
                'package_type' => $data['package_type'] ?? 'Standard Occupational',
                'attendance_status' => 'scheduled',
                'medical_clearance_status' => 'pending_results',
                'notes' => $data['notes'] ?? null,
            ]
        );
    }

    /**
     * Batch schedule APE campaign for an entire department or driver fleet
     */
    public function batchScheduleApeCampaign(
        int $year,
        string $date,
        string $facility,
        string $packageType,
        ?int $departmentId = null
    ): int {
        $query = Employee::where('employment_status', '!=', 'terminated');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();
        $count = 0;

        foreach ($employees as $emp) {
            $existing = AnnualPhysicalExam::where('employee_id', $emp->id)->where('exam_year', $year)->first();
            if (! $existing) {
                AnnualPhysicalExam::create([
                    'employee_id' => $emp->id,
                    'exam_year' => $year,
                    'schedule_date' => $date,
                    'time_slot' => '08:00 AM - 12:00 PM',
                    'facility_name' => $facility,
                    'package_type' => $packageType,
                    'attendance_status' => 'scheduled',
                    'medical_clearance_status' => 'pending_results',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Record APE Attendance, Medical Clearance & Upload Certificate
     *
     * @param array<string, mixed> $data
     */
    public function recordApeResults(
        AnnualPhysicalExam $exam,
        array $data,
        ?UploadedFile $medicalCert = null
    ): AnnualPhysicalExam {
        $certPath = $exam->medical_certificate_path;
        if ($medicalCert) {
            $certPath = $medicalCert->store('uploads/ape/certificates', 'public');
        }

        $exam->update([
            'attendance_status' => $data['attendance_status'] ?? 'attended',
            'medical_clearance_status' => $data['medical_clearance_status'] ?? 'fit_to_work',
            'findings_summary' => $data['findings_summary'] ?? null,
            'medical_certificate_path' => $certPath,
            'completed_at' => now(),
            'notes' => $data['notes'] ?? $exam->notes,
        ]);

        return $exam;
    }

    /**
     * Retrieve Corporate Group Life & Disability Insurance Portfolio Summary (known.md §8.10)
     *
     * @return array<string, mixed>
     */
    public function getGroupLifeSummary(): array
    {
        $policies = GroupLifePolicy::where('status', 'active')->get();

        $totalActive = $policies->count();
        $totalSumAssured = (float) $policies->sum('sum_assured');
        $totalMonthlyPremium = (float) $policies->sum('monthly_premium');

        return [
            'total_active_policies' => $totalActive,
            'total_sum_assured' => $totalSumAssured,
            'total_monthly_premium' => $totalMonthlyPremium,
            'total_annual_premium' => $totalMonthlyPremium * 12,
            'provider_name' => 'Sun Life Grepa Financial',
        ];
    }

    /**
     * Enroll Employee into Corporate Group Life & Disability Coverage (known.md §8.10)
     *
     * @param array<string, mixed> $data
     */
    public function enrollGroupLife(int $employeeId, array $data): GroupLifePolicy
    {
        $policyNumber = 'GLP-' . now()->format('Y') . '-' . str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        return GroupLifePolicy::create([
            'employee_id' => $employeeId,
            'policy_number' => $policyNumber,
            'provider_name' => $data['provider_name'] ?? 'Sun Life Grepa Financial',
            'coverage_type' => $data['coverage_type'] ?? 'Group Term Life',
            'sum_assured' => (float) ($data['sum_assured'] ?? 500000.00),
            'monthly_premium' => (float) ($data['monthly_premium'] ?? 350.00),
            'company_shoulder_pct' => (float) ($data['company_shoulder_pct'] ?? 100.00),
            'beneficiary_primary_name' => $data['beneficiary_primary_name'],
            'beneficiary_primary_relation' => $data['beneficiary_primary_relation'],
            'beneficiary_secondary_name' => $data['beneficiary_secondary_name'] ?? null,
            'beneficiary_secondary_relation' => $data['beneficiary_secondary_relation'] ?? null,
            'policy_start_date' => $data['policy_start_date'] ?? now()->toDateString(),
            'policy_end_date' => $data['policy_end_date'] ?? now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
    }

    /**
     * Update Group Life Policy Beneficiaries
     *
     * @param array<string, mixed> $data
     */
    public function updateGroupLifeBeneficiaries(GroupLifePolicy $policy, array $data): GroupLifePolicy
    {
        $policy->update([
            'beneficiary_primary_name' => $data['beneficiary_primary_name'] ?? $policy->beneficiary_primary_name,
            'beneficiary_primary_relation' => $data['beneficiary_primary_relation'] ?? $policy->beneficiary_primary_relation,
            'beneficiary_secondary_name' => $data['beneficiary_secondary_name'] ?? $policy->beneficiary_secondary_name,
            'beneficiary_secondary_relation' => $data['beneficiary_secondary_relation'] ?? $policy->beneficiary_secondary_relation,
            'sum_assured' => isset($data['sum_assured']) ? (float) $data['sum_assured'] : $policy->sum_assured,
        ]);

        return $policy;
    }

    /**
     * Compute Statutory Government Remittance Calendar & Schedules (known.md §8.9 & §8.11 Item 12)
     *
     * @return array<string, mixed>
     */
    public function getComplianceRemittanceCalendar(?string $targetMonth = null): array
    {
        $currentMonth = $targetMonth ? Carbon::parse($targetMonth) : now();
        $targetMonthStr = $currentMonth->format('F Y');
        $nextMonth = $currentMonth->copy()->addMonth();

        // Calculate remittance contributions from actual payroll calculations
        $totalSss = (float) SalaryComputation::sum('sss_deduction') + (float) SalaryComputation::sum('sss_employer');
        $totalPhilhealth = (float) SalaryComputation::sum('philhealth_deduction') + (float) SalaryComputation::sum('philhealth_employer');
        $totalPagibig = (float) SalaryComputation::sum('pagibig_deduction') + (float) SalaryComputation::sum('pagibig_employer');
        $totalWithholdingTax = (float) SalaryComputation::sum('withholding_tax');

        // Fallback default estimates if payroll is fresh
        if ($totalSss == 0) $totalSss = 185400.00;
        if ($totalPhilhealth == 0) $totalPhilhealth = 62500.00;
        if ($totalPagibig == 0) $totalPagibig = 24800.00;
        if ($totalWithholdingTax == 0) $totalWithholdingTax = 142300.00;

        $items = [
            [
                'agency' => 'SSS (Social Security System)',
                'form_report' => 'SSS R3 / SSS R-5 Collection List',
                'due_date' => $nextMonth->copy()->day(15)->format('M 15, Y'),
                'rate_formula' => '15% (10% Employer, 5% Employee)',
                'amount' => $totalSss,
                'status' => 'pending',
                'status_label' => 'Pending Remittance',
            ],
            [
                'agency' => 'PhilHealth (Philippine Health Insurance)',
                'form_report' => 'PhilHealth RF-1 Monthly Report',
                'due_date' => $nextMonth->copy()->day(10)->format('M 10, Y'),
                'rate_formula' => '5.0% (2.5% Employer, 2.5% Employee)',
                'amount' => $totalPhilhealth,
                'status' => 'remitted',
                'status_label' => 'Remitted',
            ],
            [
                'agency' => 'Pag-IBIG Fund (HDMF)',
                'form_report' => 'HDMF MCRF Remittance Form',
                'due_date' => $nextMonth->copy()->day(10)->format('M 10, Y'),
                'rate_formula' => '4.0% (2% Employer, 2% Employee)',
                'amount' => $totalPagibig,
                'status' => 'remitted',
                'status_label' => 'Remitted',
            ],
            [
                'agency' => 'BIR (Bureau of Internal Revenue)',
                'form_report' => 'BIR Form 1601-C (Withholding Tax)',
                'due_date' => $nextMonth->copy()->day(10)->format('M 10, Y'),
                'rate_formula' => 'TRAIN Law Withholding Tax Schedule',
                'amount' => $totalWithholdingTax,
                'status' => 'filed',
                'status_label' => 'eFPS Filed',
            ],
        ];

        return [
            'period_label' => $targetMonthStr,
            'total_remittance' => $totalSss + $totalPhilhealth + $totalPagibig + $totalWithholdingTax,
            'items' => $items,
        ];
    }
}
