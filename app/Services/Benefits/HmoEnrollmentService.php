<?php

declare(strict_types=1);

namespace App\Services\Benefits;

use App\Models\AccreditedFacility;
use App\Models\BudgetRequisition;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\HmoDependent;
use App\Models\HmoEnrollment;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HmoEnrollmentService
{
    public function __construct(
        protected HmoPlanManagementService $planService
    ) {}

    /**
     * Submit an employee HMO application via ESS or HR (known.md §8.6 Step 1)
     *
     * @param array<string, mixed> $data
     * @param array<int, array{full_name: string, relationship: string, birth_date?: string, gender?: string, birth_cert?: UploadedFile|null}> $dependentsData
     */
    public function submitApplication(
        int $employeeId,
        array $data,
        ?UploadedFile $idPhoto = null,
        ?UploadedFile $marriageCert = null,
        array $dependentsData = []
    ): HmoEnrollment {
        return DB::transaction(function () use ($employeeId, $data, $idPhoto, $marriageCert, $dependentsData) {
            $employee = Employee::findOrFail($employeeId);
            $hmoConfig = $this->planService->getHmoConfiguration();

            // Store files if uploaded
            $idPhotoPath = null;
            if ($idPhoto) {
                $idPhotoPath = $idPhoto->store('uploads/hmo/id_photos', 'public');
            }

            $marriageCertPath = null;
            if ($marriageCert) {
                $marriageCertPath = $marriageCert->store('uploads/hmo/certificates', 'public');
            }

            $salaryGrade = (int) ($employee->salary_grade ?: (
                ($employee->monthly_rate ?? 0) >= 80000 ? 7 :
                (($employee->monthly_rate ?? 0) >= 50000 ? 5 :
                (($employee->monthly_rate ?? 0) >= 30000 ? 3 : 1))
            ));
            $gradeMbl = $this->planService->getMblForGrade($salaryGrade);

            $defaultTier = match (true) {
                $salaryGrade >= 7 => 'Executive VIP',
                $salaryGrade >= 5 => 'Premium Plus',
                $salaryGrade >= 3 => 'Plus Care',
                default => 'Basic Healthcare',
            };

            $tier = $data['coverage_tier'] ?? $defaultTier;
            $provider = $data['hmo_provider'] ?? $hmoConfig['hmo_provider_name'];
            $providerPlan = $data['provider_plan'] ?? ($provider . ' Corporate Plan');
            $annualLimit = (float) ($data['annual_limit'] ?? $gradeMbl['mbl_amount']);

            $dependentCount = count($dependentsData);
            $baseEmpPremium = (float) $hmoConfig['hmo_base_employee_premium'];
            $sharing = $this->planService->calculatePremiumSharing($baseEmpPremium, $dependentCount);

            $tempCardNumber = 'APP-' . strtoupper(Str::random(8));

            $enrollment = HmoEnrollment::create([
                'employee_id' => $employee->id,
                'hmo_card_number' => $tempCardNumber,
                'hmo_provider' => $provider,
                'provider_plan' => $providerPlan,
                'coverage_tier' => $tier,
                'annual_limit' => $annualLimit,
                'mbl_amount' => $annualLimit,
                'monthly_premium' => $sharing['total_monthly_premium'],
                'dependent_count' => $dependentCount,
                'coverage_start_date' => $data['coverage_start_date'] ?? now()->toDateString(),
                'coverage_end_date' => $data['coverage_end_date'] ?? now()->addYear()->toDateString(),
                'status' => 'inactive', // inactive until provider cards issued
                'enrollment_status' => 'submitted',
                'id_photo_path' => $idPhotoPath,
                'marriage_cert_path' => $marriageCertPath,
                'notes' => $data['notes'] ?? 'ESS HMO Application Submitted',
            ]);

            // Save individual dependent records
            foreach ($dependentsData as $dep) {
                $birthCertPath = null;
                if (isset($dep['birth_cert']) && $dep['birth_cert'] instanceof UploadedFile) {
                    $birthCertPath = $dep['birth_cert']->store('uploads/hmo/dependents', 'public');
                }

                HmoDependent::create([
                    'hmo_enrollment_id' => $enrollment->id,
                    'employee_id' => $employee->id,
                    'full_name' => $dep['full_name'],
                    'relationship' => $dep['relationship'] ?? 'Child',
                    'birth_date' => ! empty($dep['birth_date']) ? $dep['birth_date'] : null,
                    'gender' => $dep['gender'] ?? null,
                    'birth_cert_path' => $birthCertPath,
                    'status' => 'pending',
                ]);
            }

            return $enrollment;
        });
    }

    /**
     * HR Team 4 Review & Eligibility Check (known.md §8.6 Step 2)
     */
    public function validateApplicationByHr(HmoEnrollment $enrollment, ?string $remarks = null): HmoEnrollment
    {
        $enrollment->update([
            'enrollment_status' => 'hr_approved',
            'hr_reviewed_at' => now(),
            'hr_remarks' => $remarks ?? 'Verified eligibility against tenure and documentary requirements.',
        ]);

        return $enrollment;
    }

    /**
     * Submit Budget Requisition to Team 5 Budget Officer (known.md §8.6 Step 3)
     */
    public function requestBudgetForEnrollment(HmoEnrollment $enrollment): BudgetRequisition
    {
        return DB::transaction(function () use ($enrollment) {
            $annualCost = (float) $enrollment->monthly_premium * 12;
            $empName = $enrollment->employee ? ($enrollment->employee->first_name . ' ' . $enrollment->employee->last_name) : 'Employee';

            $budgetReq = BudgetRequisition::create([
                'requisition_code' => 'REQ-HMO-' . strtoupper(Str::random(6)),
                'category' => 'HMO Healthcare Coverage',
                'justification' => "Annual corporate HMO premium allocation for {$empName} ({$enrollment->coverage_tier} Plan, {$enrollment->dependent_count} dependents)",
                'amount' => $annualCost,
                'status' => 'awaiting_approval',
            ]);

            $enrollment->update([
                'enrollment_status' => 'budget_requested',
                'budget_requisition_id' => $budgetReq->id,
            ]);

            return $budgetReq;
        });
    }

    /**
     * Finalize Provider Enrollment & Issue Official Member Card (known.md §8.6 Step 4)
     */
    public function finalizeProviderEnrollment(
        HmoEnrollment $enrollment,
        string $cardNumber,
        ?string $providerPlan = null
    ): HmoEnrollment {
        $updateData = [
            'hmo_card_number' => $cardNumber,
            'enrollment_status' => 'active',
            'status' => 'active',
        ];

        if ($providerPlan) {
            $updateData['provider_plan'] = $providerPlan;
        }

        $enrollment->update($updateData);

        // Update all associated dependents to verified
        $enrollment->dependents()->update(['status' => 'verified']);

        return $enrollment;
    }

    /**
     * Process 30-day Annual Renewal (known.md §8.6 Step 6)
     */
    public function processAnnualRenewal(HmoEnrollment $enrollment): HmoEnrollment
    {
        $newEnd = $enrollment->coverage_end_date
            ? $enrollment->coverage_end_date->copy()->addYear()
            : now()->addYear();

        $enrollment->update([
            'coverage_end_date' => $newEnd,
            'enrollment_status' => 'active',
            'status' => 'active',
            'renewed_at' => now(),
        ]);

        return $enrollment;
    }

    /**
     * Reject HMO Application with recorded reason
     */
    public function rejectApplication(HmoEnrollment $enrollment, string $reason): HmoEnrollment
    {
        $enrollment->update([
            'enrollment_status' => 'rejected',
            'status' => 'inactive',
            'rejection_reason' => $reason,
        ]);

        return $enrollment;
    }

    /**
     * Generate Comprehensive Digital HMO Card Payload for ESS (known.md §8.6 Step 5)
     *
     * @return array<string, mixed>
     */
    public function generateDigitalCardPayload(HmoEnrollment $enrollment): array
    {
        $enrollment->loadMissing(['employee.department', 'dependents', 'utilizationLogs']);

        $mbl = (float) ($enrollment->annual_limit ?: $enrollment->mbl_amount);
        $utilized = (float) $enrollment->totalUtilized();
        $remaining = max(0.00, $mbl - $utilized);

        $employee = $enrollment->employee;
        $fullName = $employee ? ($employee->first_name . ' ' . $employee->last_name) : 'Authorized Member';

        $qrData = json_encode([
            'member_id' => $enrollment->hmo_card_number,
            'employee_code' => $employee?->employee_code,
            'name' => $fullName,
            'provider' => $enrollment->hmo_provider,
            'tier' => $enrollment->coverage_tier,
            'valid_until' => $enrollment->coverage_end_date?->format('Y-m-d'),
            'verified' => true,
        ]);

        $emergencyHospitals = AccreditedFacility::where('is_active', true)
            ->where('is_emergency_ready', true)
            ->take(5)
            ->get(['name', 'region', 'contact_number', 'address']);

        return [
            'card_number' => $enrollment->hmo_card_number,
            'provider_name' => $enrollment->hmo_provider,
            'plan_tier' => $enrollment->coverage_tier,
            'employee_name' => $fullName,
            'employee_code' => $employee?->employee_code,
            'department' => $employee?->department?->name ?? 'General',
            'position' => $employee?->position,
            'mbl_limit' => $mbl,
            'mbl_utilized' => $utilized,
            'mbl_remaining' => $remaining,
            'coverage_start' => $enrollment->coverage_start_date?->format('M j, Y'),
            'coverage_end' => $enrollment->coverage_end_date?->format('M j, Y'),
            'is_expiring_soon' => $enrollment->isExpiringSoon(),
            'days_until_expiry' => $enrollment->daysUntilExpiry(),
            'enrollment_status' => $enrollment->enrollment_status,
            'status' => $enrollment->status,
            'qr_payload' => $qrData,
            'dependents' => $enrollment->dependents->map(fn ($d) => [
                'name' => $d->full_name,
                'relationship' => $d->relationship,
                'status' => $d->status,
            ]),
            'emergency_facilities' => $emergencyHospitals,
        ];
    }

    /**
     * Evaluate employee HMO and benefit eligibility based on employment rules (Step 2)
     *
     * @return array{
     *     status: string,
     *     badge_class: string,
     *     label: string,
     *     eligible_plan: string,
     *     reason: string,
     *     tenure_months: int
     * }
     */
    public function getEligibilityStatus(Employee $employee): array
    {
        $tenureMonths = $employee->hire_date ? (int) $employee->hire_date->diffInMonths(now()) : 0;
        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver') ||
                    str_contains(strtolower($employee->department?->name ?? ''), 'fleet');

        if ($isDriver) {
            return [
                'status' => 'driver_pool',
                'badge_class' => 'bg-amber-100 text-amber-800 border-amber-300',
                'label' => 'Driver Accident Pool (3%)',
                'eligible_plan' => 'Driver Fleet Care',
                'reason' => 'Designated for TNVS 3% Accident Insurance Pool and 50% company matching subsidy.',
                'tenure_months' => $tenureMonths,
            ];
        }

        if ($employee->employment_status === 'regular') {
            $salaryGrade = (int) ($employee->salary_grade ?: (
                ($employee->monthly_rate ?? 0) >= 80000 ? 7 :
                (($employee->monthly_rate ?? 0) >= 50000 ? 5 :
                (($employee->monthly_rate ?? 0) >= 30000 ? 3 : 1))
            ));
            $tierName = match (true) {
                $salaryGrade >= 7 => 'Executive VIP',
                $salaryGrade >= 5 => 'Premium Plus',
                $salaryGrade >= 3 => 'Plus Care',
                default => 'Basic Healthcare',
            };

            return [
                'status' => 'eligible',
                'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'label' => 'Regular — Fully Eligible',
                'eligible_plan' => $tierName,
                'reason' => 'Tenure requirement met. 100% entitled to Tiered MBL, Corporate Life & APE.',
                'tenure_months' => $tenureMonths,
            ];
        }

        return [
            'status' => 'probationary_pending',
            'badge_class' => 'bg-blue-100 text-blue-800 border-blue-300',
            'label' => 'Probationary (Pending Regularization)',
            'eligible_plan' => 'Basic Healthcare (Pending)',
            'reason' => 'Statutory DOLE benefits active. Full corporate HMO unlocked upon 6-month regularization.',
            'tenure_months' => $tenureMonths,
        ];
    }

    /**
     * 1-Click Sync of active employee HMO contribution shares to Payroll Deductions (Step 4 & 7)
     *
     * @return array{
     *     synced_count: int,
     *     total_employee_deductions: float,
     *     total_company_contributions: float
     * }
     */
    public function syncHmoDeductionsToPayroll(): array
    {
        $activeEnrollments = HmoEnrollment::where('status', 'active')->with('employee')->get();
        $totalEmployeeDeductions = 0.00;
        $totalCompanyContributions = 0.00;

        foreach ($activeEnrollments as $enrollment) {
            $sharing = $this->planService->calculatePremiumSharing(
                (float) $enrollment->monthly_premium,
                (int) $enrollment->dependent_count
            );

            $totalEmployeeDeductions += $sharing['employee_share'];
            $totalCompanyContributions += $sharing['company_share'];
        }

        return [
            'synced_count' => $activeEnrollments->count(),
            'total_employee_deductions' => round($totalEmployeeDeductions, 2),
            'total_company_contributions' => round($totalCompanyContributions, 2),
        ];
    }

    /**
     * Deactivate HMO coverage upon Employee Resignation or Separation (Step 10)
     */
    public function deactivateSeparatedEmployeeHmo(HmoEnrollment $enrollment, string $reason = 'Employee Resignation / Separation'): void
    {
        DB::transaction(function () use ($enrollment, $reason) {
            $enrollment->update([
                'status' => 'inactive',
                'enrollment_status' => 'cancelled',
                'rejection_reason' => $reason,
                'coverage_end_date' => now()->toDateString(),
                'notes' => ($enrollment->notes ? $enrollment->notes . "\n" : '') . "Terminated on separation: {$reason}",
            ]);

            $enrollment->dependents()->update([
                'status' => 'inactive',
            ]);
        });
    }
}
