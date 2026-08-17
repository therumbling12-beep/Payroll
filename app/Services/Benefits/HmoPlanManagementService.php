<?php

declare(strict_types=1);

namespace App\Services\Benefits;

use App\Models\AccreditedFacility;
use App\Models\CompanySetting;
use App\Models\HmoEnrollment;
use App\Models\HmoGradeLimit;
use App\Models\SalaryGrade;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HmoPlanManagementService
{
    /**
     * Retrieve active HMO enterprise configuration policies (known.md §8.4)
     *
     * @return array<string, mixed>
     */
    public function getHmoConfiguration(): array
    {
        return [
            'hmo_has_provider' => (bool) CompanySetting::getValue('hmo_has_provider', true),
            'hmo_provider_name' => (string) CompanySetting::getValue('hmo_provider_name', 'Maxicare Healthcare Corporation'),
            'hmo_plan_type' => (string) CompanySetting::getValue('hmo_plan_type', 'Comprehensive'),
            'hmo_premium_shoulder_type' => (string) CompanySetting::getValue('hmo_premium_shoulder_type', 'shared'),
            'hmo_company_share_pct' => (float) CompanySetting::getValue('hmo_company_share_pct', 80.0),
            'hmo_employee_share_pct' => (float) CompanySetting::getValue('hmo_employee_share_pct', 20.0),
            'hmo_coverage_start_months' => (int) CompanySetting::getValue('hmo_coverage_start_months', 6),
            'hmo_dependent_coverage' => (bool) CompanySetting::getValue('hmo_dependent_coverage', true),
            'hmo_max_dependents' => (int) CompanySetting::getValue('hmo_max_dependents', 4),
            'hmo_base_employee_premium' => (float) CompanySetting::getValue('hmo_base_employee_premium', 1800.00),
            'hmo_base_dependent_premium' => (float) CompanySetting::getValue('hmo_base_dependent_premium', 1200.00),
        ];
    }

    /**
     * Update HMO enterprise configuration policies
     *
     * @param array<string, mixed> $data
     */
    public function updateHmoConfiguration(array $data): void
    {
        $keys = [
            'hmo_has_provider' => $data['hmo_has_provider'] ?? '1',
            'hmo_provider_name' => $data['hmo_provider_name'] ?? 'Maxicare Healthcare Corporation',
            'hmo_plan_type' => $data['hmo_plan_type'] ?? 'Comprehensive',
            'hmo_premium_shoulder_type' => $data['hmo_premium_shoulder_type'] ?? 'shared',
            'hmo_company_share_pct' => (string) ($data['hmo_company_share_pct'] ?? 80),
            'hmo_employee_share_pct' => (string) ($data['hmo_employee_share_pct'] ?? 20),
            'hmo_coverage_start_months' => (string) ($data['hmo_coverage_start_months'] ?? 6),
            'hmo_dependent_coverage' => $data['hmo_dependent_coverage'] ?? '1',
            'hmo_max_dependents' => (string) ($data['hmo_max_dependents'] ?? 4),
            'hmo_base_employee_premium' => (string) ($data['hmo_base_employee_premium'] ?? 1800.00),
            'hmo_base_dependent_premium' => (string) ($data['hmo_base_dependent_premium'] ?? 1200.00),
        ];

        foreach ($keys as $key => $val) {
            CompanySetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $val, 'description' => 'HMO Policy Configuration']
            );
        }
    }

    /**
     * Reset HMO enterprise configuration policies to factory defaults
     */
    public function resetHmoConfigurationToDefaults(): void
    {
        $defaults = [
            'hmo_has_provider' => '1',
            'hmo_provider_name' => 'Maxicare Healthcare Corporation',
            'hmo_plan_type' => 'Comprehensive',
            'hmo_premium_shoulder_type' => 'shared',
            'hmo_company_share_pct' => '80',
            'hmo_employee_share_pct' => '20',
            'hmo_coverage_start_months' => '6',
            'hmo_dependent_coverage' => '1',
            'hmo_max_dependents' => '4',
            'hmo_base_employee_premium' => '1800.00',
            'hmo_base_dependent_premium' => '1200.00',
        ];

        foreach ($defaults as $key => $val) {
            CompanySetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $val, 'description' => 'HMO Policy Configuration']
            );
        }
    }

    /**
     * Get all active Grade-Based MBL limit definitions (known.md §8.5)
     *
     * @return Collection<int, HmoGradeLimit>
     */
    public function getGradeMblMatrix(): Collection
    {
        return HmoGradeLimit::where('is_active', true)->orderBy('grade_min')->get();
    }

    /**
     * Resolve MBL amount and Room & Board category for a specific salary grade
     *
     * @return array{
     *     grade_level: int,
     *     mbl_amount: float,
     *     room_and_board: string,
     *     room_label: string,
     *     max_dependents: int,
     *     title: string
     * }
     */
    public function getMblForGrade(int $salaryGradeLevel): array
    {
        $limit = HmoGradeLimit::where('grade_min', '<=', $salaryGradeLevel)
            ->where('grade_max', '>=', $salaryGradeLevel)
            ->where('is_active', true)
            ->first();

        if (! $limit) {
            // Fallback for Grade 1
            return [
                'grade_level' => $salaryGradeLevel,
                'mbl_amount' => 100000.00,
                'room_and_board' => 'semi_private',
                'room_label' => 'Semi-Private Room',
                'max_dependents' => 0,
                'title' => "Salary Grade {$salaryGradeLevel} Standard Baseline",
            ];
        }

        return [
            'grade_level' => $salaryGradeLevel,
            'mbl_amount' => (float) $limit->mbl_amount,
            'room_and_board' => (string) $limit->room_and_board,
            'room_label' => $limit->room_label,
            'max_dependents' => (int) $limit->max_dependents,
            'title' => $limit->title,
        ];
    }

    /**
     * Calculate premium co-sharing between Employer and Employee
     *
     * @return array{
     *     total_monthly_premium: float,
     *     company_share: float,
     *     employee_share: float,
     *     company_share_pct: float,
     *     employee_share_pct: float,
     *     dependent_premium_total: float
     * }
     */
    public function calculatePremiumSharing(float $totalMonthlyPremium, int $dependentCount = 0): array
    {
        $config = $this->getHmoConfiguration();

        $shoulderType = $config['hmo_premium_shoulder_type'];
        $companyPct = (float) $config['hmo_company_share_pct'];
        $employeePct = (float) $config['hmo_employee_share_pct'];

        if ($shoulderType === 'company') {
            $companyPct = 100.0;
            $employeePct = 0.0;
        }

        $baseEmpPremium = (float) $config['hmo_base_employee_premium'];
        $baseDepPremium = (float) $config['hmo_base_dependent_premium'];
        $dependentTotal = $dependentCount * $baseDepPremium;

        $effectiveTotal = max($totalMonthlyPremium, $baseEmpPremium + $dependentTotal);

        $companyShare = round($effectiveTotal * ($companyPct / 100.0), 2);
        $employeeShare = round($effectiveTotal - $companyShare, 2);

        return [
            'total_monthly_premium' => $effectiveTotal,
            'company_share' => $companyShare,
            'employee_share' => $employeeShare,
            'company_share_pct' => $companyPct,
            'employee_share_pct' => $employeePct,
            'dependent_premium_total' => $dependentTotal,
        ];
    }

    /**
     * Get searchable and filterable list of accredited medical facilities
     */
    public function getAccreditedFacilities(
        ?string $search = null,
        ?string $type = null,
        ?string $region = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = AccreditedFacility::where('is_active', true)->latest();

        if ($search) {
            $query->search($search);
        }

        if ($type && $type !== 'all') {
            $query->where('facility_type', $type);
        }

        if ($region && $region !== 'all') {
            $query->where('region', $region);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Stream official Provider Master Roster CSV export for HMO provider upload
     */
    public function exportProviderRosterCsv(): StreamedResponse
    {
        $enrollments = HmoEnrollment::with(['employee.department'])->where('status', 'active')->get();
        $config = $this->getHmoConfiguration();

        $filename = 'hmo_provider_master_roster_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($enrollments, $config) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Provider Name',
                'HMO Member ID / Card No',
                'Employee Code',
                'Full Name',
                'Position',
                'Department',
                'Plan Tier',
                'MBL Annual Limit (PHP)',
                'Monthly Premium (PHP)',
                'Company Share (PHP)',
                'Employee Share (PHP)',
                'Dependents Count',
                'Coverage Start',
                'Coverage End',
                'Status',
            ]);

            foreach ($enrollments as $e) {
                $sharing = $this->calculatePremiumSharing((float) $e->monthly_premium, (int) $e->dependent_count);

                fputcsv($file, [
                    $e->hmo_provider ?: $config['hmo_provider_name'],
                    $e->hmo_card_number,
                    $e->employee?->employee_code,
                    $e->employee ? ($e->employee->first_name . ' ' . $e->employee->last_name) : '—',
                    $e->employee?->position,
                    $e->employee?->department?->name ?? 'General',
                    $e->coverage_tier ?? $e->provider_plan,
                    number_format((float) ($e->annual_limit ?: $e->mbl_amount), 2, '.', ''),
                    number_format((float) $e->monthly_premium, 2, '.', ''),
                    number_format($sharing['company_share'], 2, '.', ''),
                    number_format($sharing['employee_share'], 2, '.', ''),
                    $e->dependent_count ?? 0,
                    $e->coverage_start_date?->format('Y-m-d'),
                    $e->coverage_end_date?->format('Y-m-d'),
                    ucfirst((string) $e->status),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Check if the annual Open Enrollment period is currently active
     */
    public function isOpenEnrollmentActive(): bool
    {
        $enabled = CompanySetting::getValue('open_enrollment_enabled', '1');
        if ($enabled === '0' || $enabled === false) {
            return false;
        }

        $startDateStr = CompanySetting::getValue('open_enrollment_start_date');
        $endDateStr = CompanySetting::getValue('open_enrollment_end_date');

        if (! $startDateStr || ! $endDateStr) {
            // Default active window if not explicitly constrained
            return true;
        }

        try {
            $today = now()->startOfDay();
            $start = \Carbon\Carbon::parse($startDateStr)->startOfDay();
            $end = \Carbon\Carbon::parse($endDateStr)->endOfDay();

            return $today->between($start, $end);
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Get the configured Open Enrollment date window
     *
     * @return array{start_date: string, end_date: string, is_active: bool}
     */
    public function getOpenEnrollmentWindow(): array
    {
        $startDate = (string) CompanySetting::getValue('open_enrollment_start_date', date('Y-11-01'));
        $endDate = (string) CompanySetting::getValue('open_enrollment_end_date', date('Y-11-30'));

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $this->isOpenEnrollmentActive(),
        ];
    }

    /**
     * Export the Grade-Based Maximum Benefit Limit (MBL) Matrix & Plan Catalog as CSV
     */
    public function exportPlansMatrixCsv(): StreamedResponse
    {
        $limits = $this->getGradeMblMatrix();
        $config = $this->getHmoConfiguration();
        $fileName = 'tripwise_hmo_plans_matrix_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($limits, $config) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Salary Grade Range',
                'Plan Tier Name',
                'MBL Annual Limit (PHP)',
                'Room & Board Category',
                'Max Dependents Allowed',
                'Base Employee Premium (PHP)',
                'Company Share (%)',
                'Employee Share (%)',
                'Special Coverage / Notes',
            ]);

            foreach ($limits as $limit) {
                $gradeRange = $limit->grade_min === $limit->grade_max
                    ? "Grade {$limit->grade_min}"
                    : "Grade {$limit->grade_min} – Grade {$limit->grade_max}";

                $roomLabel = match ($limit->room_and_board) {
                    'suite' => 'Executive Suite Room',
                    'private' => 'Private Room',
                    'semi_private' => 'Semi-Private Room',
                    default => 'Ward / Standard Room',
                };

                fputcsv($file, [
                    $gradeRange,
                    $limit->tier_name,
                    number_format((float) $limit->mbl_amount, 2, '.', ''),
                    $roomLabel,
                    $limit->max_dependents,
                    number_format((float) $config['hmo_base_employee_premium'], 2, '.', ''),
                    number_format((float) $config['hmo_company_share_pct'], 1, '.', '') . '%',
                    number_format((float) $config['hmo_employee_share_pct'], 1, '.', '') . '%',
                    $limit->benefits_description ?? 'Standard corporate coverage and accredited clinic access.',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
