<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Benefits\ChristmasBonusService;
use App\Services\Benefits\MealAllowanceService;
use App\Services\Benefits\ServiceIncentiveLeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BenefitsController extends Controller
{
    public function __construct(
        protected ServiceIncentiveLeaveService $silService,
        protected MealAllowanceService $mealService,
        protected ChristmasBonusService $christmasBonusService
    ) {}

    /**
     * Default Redirect to Service Incentive Leave Sub-Page
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('benefits.sil');
    }

    /**
     * Sub-Page 1: Service Incentive Leave (SIL) Tracker & Cash Conversion (DOLE Art. 95)
     */
    public function sil(Request $request): View
    {
        $search = $request->query('search');
        $departmentId = $request->query('department_id');
        $year = (int) $request->query('year', (string) date('Y'));

        $departments = Department::orderBy('name')->get();
        $rosterData = $this->silService->getRosterData($year, $search, $departmentId, 15);

        return view('payroll-benefits.benefits.sil', [
            'employees' => $rosterData['employees'],
            'silRoster' => $rosterData['silRoster'],
            'departments' => $departments,
            'stats' => $rosterData['stats'],
            'search' => $search,
            'departmentId' => $departmentId,
            'currentYear' => $year,
        ]);
    }

    /**
     * Record SIL Leave Days Taken by Employee
     */
    public function recordSil(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'year' => ['nullable', 'integer'],
            'days_taken' => ['required', 'integer', 'min:1', 'max:15'],
            'leave_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $year = (int) ($validated['year'] ?? date('Y'));
        $daysTaken = (int) $validated['days_taken'];

        $this->silService->logLeaveUsage(
            $employee,
            $year,
            $daysTaken,
            $validated['leave_date'],
            $validated['notes'] ?? null
        );

        // Keep registry sync for legacy compatibility
        $silUsageMap = json_decode((string) CompanySetting::getValue('sil_usage_registry', '{}'), true) ?: [];
        $currentUsed = (int) ($silUsageMap[(string) $employee->id]['used_days'] ?? 0);
        $silUsageMap[(string) $employee->id] = [
            'used_days' => $currentUsed + $daysTaken,
            'last_date' => $validated['leave_date'],
            'last_notes' => $validated['notes'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ];
        CompanySetting::setValue('sil_usage_registry', json_encode($silUsageMap));

        $redirectParams = ($year !== (int) date('Y')) ? ['year' => $year] : [];

        return redirect()->route('benefits.sil', $redirectParams)
            ->with('status', "Recorded {$daysTaken} day(s) of Service Incentive Leave for {$employee->first_name} {$employee->last_name}.");
    }

    /**
     * Convert unused SIL leave balance to cash compensation (DOLE Art. 95)
     */
    public function convertSilCash(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'year' => ['nullable', 'integer'],
            'days_to_convert' => ['nullable', 'integer', 'min:1'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $year = (int) ($validated['year'] ?? date('Y'));
        $daysToConvert = ! empty($validated['days_to_convert']) ? (int) $validated['days_to_convert'] : null;

        $record = $this->silService->convertUnusedToCash($employee, $year, $daysToConvert);

        $redirectParams = ($year !== (int) date('Y')) ? ['year' => $year] : [];

        return redirect()->route('benefits.sil', $redirectParams)
            ->with('status', "Successfully converted {$record->cash_converted_days} SIL leave day(s) to PHP " . number_format($record->cash_converted_amount, 2) . " cash compensation for {$employee->first_name} {$employee->last_name}.");
    }

    /**
     * Annual SIL Reset / Rollover Workflow
     */
    public function resetSilYear(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_year' => ['required', 'integer', 'min:2020', 'max:2099'],
        ]);

        $targetYear = (int) $validated['target_year'];
        $result = $this->silService->resetAnnualPool($targetYear);

        return redirect()->route('benefits.sil', ['year' => $targetYear])
            ->with('status', "Annual SIL pool initialized for Year {$targetYear}. Total active employees initialized: {$result['total_processed']} ({$result['total_entitled']} qualified).");
    }

    /**
     * Export SIL Annual Roster CSV
     */
    public function exportSilCsv(Request $request): StreamedResponse
    {
        $year = (int) $request->query('year', (string) date('Y'));
        $departmentId = $request->query('department_id');

        $query = Employee::with(['department', 'serviceIncentiveLeaves' => fn ($q) => $q->where('year', $year)])
            ->where('employment_status', '!=', 'terminated')
            ->orderBy('first_name');

        if ($departmentId && $departmentId !== 'all') {
            $query->department($departmentId);
        }

        $employees = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="SIL_Roster_Year_' . $year . '_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($employees, $year) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            // CSV Header
            fputcsv($handle, [
                'Employee Code',
                'Full Name',
                'Department',
                'Position',
                'Hire Date',
                'Years of Service',
                'Entitled to SIL',
                'Entitled Days',
                'Used Days',
                'Converted Days',
                'Remaining Days',
                'Daily Rate (PHP)',
                'Cash Conversion Value (PHP)',
                'Status',
            ]);

            foreach ($employees as $emp) {
                $record = $emp->serviceIncentiveLeaves->first() ?: $this->silService->getOrCreateAnnualRecord($emp, $year);
                $dailyRate = (float) ($emp->daily_rate ?: ($emp->monthly_rate ? $emp->monthly_rate / 26 : 0.00));
                $cashValue = round($record->remaining_days * $dailyRate, 2);

                fputcsv($handle, [
                    $emp->employee_code,
                    "{$emp->first_name} {$emp->last_name}",
                    $emp->department?->name ?? 'N/A',
                    $emp->position,
                    $emp->hire_date?->format('Y-m-d') ?? 'N/A',
                    number_format($emp->years_of_service, 1) . ' yrs',
                    $record->entitled_days > 0 ? 'YES' : 'NO',
                    $record->entitled_days,
                    $record->used_days,
                    $record->cash_converted_days,
                    $record->remaining_days,
                    number_format($dailyRate, 2, '.', ''),
                    number_format($cashValue, 2, '.', ''),
                    strtoupper($record->status),
                ]);
            }

            fclose($handle);
        }, 'SIL_Roster_Year_' . $year . '.csv', $headers);
    }

    /**
     * Update SIL Annual Entitlement Days Policy
     */
    public function updateSilSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sil_annual_days' => ['required', 'integer', 'min:1', 'max:30'],
            'sil_min_tenure_years' => ['nullable', 'numeric', 'min:0.1', 'max:5.0'],
        ]);

        CompanySetting::setValue('sil_annual_days', (string) $validated['sil_annual_days']);
        if (! empty($validated['sil_min_tenure_years'])) {
            CompanySetting::setValue('sil_min_tenure_years', (string) $validated['sil_min_tenure_years']);
        }

        return redirect()->route('benefits.sil')
            ->with('status', 'Service Incentive Leave annual policy updated successfully.');
    }

    /**
     * Sub-Page 2: Meal Allowance Management & Roster (BIR RR 11-2018 De Minimis)
     */
     public function mealAllowance(Request $request): View
     {
         $search = $request->query('search');
         $departmentId = $request->query('department_id');
         $cutoff = $request->query('cutoff');

         $departments = Department::orderBy('name')->get();
         $data = $this->mealService->getRosterAndDisbursementData($cutoff, $search, $departmentId, 15);

         return view('payroll-benefits.benefits.meal-allowance', [
             'employees' => $data['employees'],
             'roster' => $data['roster'],
             'departments' => $departments,
             'stats' => $data['stats'],
             'search' => $search,
             'departmentId' => $departmentId,
             'activeCutoff' => $data['activeCutoff'],
         ]);
     }

    /**
     * Batch generate meal allowance disbursements for a payroll cutoff period
     */
    public function generateMealDisbursements(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cutoff_period' => ['required', 'string', 'max:50'],
            'daily_rate' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $cutoff = $validated['cutoff_period'];
        $dailyRate = ! empty($validated['daily_rate']) ? (float) $validated['daily_rate'] : null;
        $deptId = ! empty($validated['department_id']) ? (int) $validated['department_id'] : null;

        $result = $this->mealService->batchGenerateForCutoff($cutoff, $dailyRate, $deptId);

        return redirect()->route('benefits.meal-allowance', ['cutoff' => $cutoff])
            ->with('status', "Generated {$result['total_generated']} meal allowance disbursements for Cutoff {$cutoff} (Total Outlay: PHP " . number_format($result['total_gross_outlay'], 2) . ").");
    }

    /**
     * Approve pending meal allowance disbursements for a cutoff
     */
    public function approveMealDisbursements(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cutoff_period' => ['required', 'string', 'max:50'],
        ]);

        $cutoff = $validated['cutoff_period'];
        $count = $this->mealService->approveBatch($cutoff);

        return redirect()->route('benefits.meal-allowance', ['cutoff' => $cutoff])
            ->with('status', "Approved {$count} meal allowance disbursements for Cutoff {$cutoff}. Ready for payroll release.");
    }

    /**
     * Release and disburse meal allowance batch to payroll
     */
    public function releaseMealDisbursements(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cutoff_period' => ['required', 'string', 'max:50'],
        ]);

        $cutoff = $validated['cutoff_period'];
        $count = $this->mealService->releaseBatchToPayroll($cutoff);

        return redirect()->route('benefits.meal-allowance', ['cutoff' => $cutoff])
            ->with('status', "Released {$count} meal allowance disbursements to payroll for Cutoff {$cutoff}.");
    }

    /**
     * Update Meal Allowance Policy Settings
     */
    public function updateMealAllowanceSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meal_allowance_daily' => ['required', 'numeric', 'min:0', 'max:2000'],
            'meal_de_minimis_weekly_cap' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'meal_allowance_schedule' => ['nullable', 'string', 'max:255'],
            'meal_allowance_eligibility' => ['nullable', 'string', 'max:255'],
            'meal_allowance_driver_auto' => ['nullable', 'boolean'],
        ]);

        CompanySetting::setValue('meal_allowance_daily', (string) $validated['meal_allowance_daily']);
        if (! empty($validated['meal_de_minimis_weekly_cap'])) {
            CompanySetting::setValue('meal_de_minimis_weekly_cap', (string) $validated['meal_de_minimis_weekly_cap']);
        }
        if (! empty($validated['meal_allowance_schedule'])) {
            CompanySetting::setValue('meal_allowance_schedule', $validated['meal_allowance_schedule']);
        }
        if (! empty($validated['meal_allowance_eligibility'])) {
            CompanySetting::setValue('meal_allowance_eligibility', $validated['meal_allowance_eligibility']);
        }
        CompanySetting::setValue('meal_allowance_driver_auto', $request->has('meal_allowance_driver_auto') ? '1' : '0');

        return redirect()->route('benefits.meal-allowance')
            ->with('status', 'Meal allowance rates, schedule, and De Minimis ceiling updated successfully.');
    }

    /**
     * Export Meal Allowance Allocation Roster CSV
     */
    public function exportMealAllowanceCsv(Request $request): StreamedResponse
    {
        $cutoff = $request->query('cutoff', (string) CompanySetting::getValue('payroll_current_cutoff', date('Y-m-d')));
        $departmentId = $request->query('department_id');

        $query = \App\Models\Employee::with(['department', 'mealAllowanceDisbursements' => fn ($q) => $q->where('cutoff_period', $cutoff)])
            ->where('employment_status', '!=', 'terminated')
            ->orderBy('first_name');

        if ($departmentId && $departmentId !== 'all') {
            $query->department($departmentId);
        }

        $employees = $query->get();
        $mealDailyRate = (float) CompanySetting::getValue('meal_allowance_daily', 60.00);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Meal_Allowance_Disbursements_' . $cutoff . '_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($employees, $cutoff, $mealDailyRate) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'Employee Code',
                'Employee Name',
                'Department',
                'Position',
                'Cutoff Period',
                'Days / Shifts Rendered',
                'Daily Subsidy Rate (PHP)',
                'Gross Allowance (PHP)',
                'Tax-Exempt De Minimis (PHP)',
                'Taxable Excess (PHP)',
                'Status',
            ]);

            foreach ($employees as $emp) {
                $disb = $emp->mealAllowanceDisbursements->first();
                if ($disb) {
                    $days = $disb->days_rendered;
                    $rate = $disb->daily_subsidy_rate;
                    $gross = $disb->gross_amount;
                    $exempt = $disb->tax_exempt_amount;
                    $taxable = $disb->taxable_excess_amount;
                    $status = strtoupper($disb->status);
                } else {
                    $comp = $this->mealService->computeForEmployee($emp, $cutoff, $mealDailyRate);
                    $days = $comp['days_rendered'];
                    $rate = $comp['daily_subsidy_rate'];
                    $gross = $comp['gross_amount'];
                    $exempt = $comp['tax_exempt_amount'];
                    $taxable = $comp['taxable_excess_amount'];
                    $status = 'UNPROCESSED';
                }

                fputcsv($handle, [
                    $emp->employee_code,
                    "{$emp->first_name} {$emp->last_name}",
                    $emp->department?->name ?? 'General Fleet',
                    $emp->position,
                    $cutoff,
                    $days,
                    number_format($rate, 2, '.', ''),
                    number_format($gross, 2, '.', ''),
                    number_format($exempt, 2, '.', ''),
                    number_format($taxable, 2, '.', ''),
                    $status,
                ]);
            }

            fclose($handle);
        }, 'Meal_Allowance_Disbursements_' . $cutoff . '.csv', $headers);
    }

    /**
     * Sub-Page 3: Christmas Bonus Policy & Year-End Allocation
     */
    public function christmasBonus(Request $request): View
    {
        $search = $request->query('search');
        $departmentId = $request->query('department_id');
        $year = (int) $request->query('year', (string) date('Y'));

        $departments = Department::orderBy('name')->get();
        $data = $this->christmasBonusService->getRosterAndDisbursementData($year, $search, $departmentId, 15);

        return view('payroll-benefits.benefits.christmas-bonus', [
            'employees' => $data['employees'],
            'roster' => $data['roster'],
            'departments' => $departments,
            'stats' => $data['stats'],
            'search' => $search,
            'departmentId' => $departmentId,
            'currentYear' => $year,
        ]);
    }

    /**
     * Batch generate Christmas Bonus allocation records for a calendar year
     */
    public function generateChristmasBonus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bonus_year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'bonus_amount' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'min_months' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        $year = (int) $validated['bonus_year'];
        $bonusAmount = ! empty($validated['bonus_amount']) ? (float) $validated['bonus_amount'] : null;
        $minMonths = ! empty($validated['min_months']) ? (int) $validated['min_months'] : null;

        $result = $this->christmasBonusService->batchGenerateForYear($year, $bonusAmount, $minMonths);

        return redirect()->route('benefits.christmas-bonus', ['year' => $year])
            ->with('status', "Generated {$result['total_generated']} Christmas Bonus allocation records for Year {$year} (Total Budget: PHP " . number_format($result['total_outlay'], 2) . ").");
    }

    /**
     * Approve pending Christmas Bonus allocation batch
     */
    public function approveChristmasBonus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bonus_year' => ['required', 'integer', 'min:2020', 'max:2099'],
        ]);

        $year = (int) $validated['bonus_year'];
        $count = $this->christmasBonusService->approveBatch($year);

        return redirect()->route('benefits.christmas-bonus', ['year' => $year])
            ->with('status', "HR approved {$count} Christmas Bonus allocation records for Year {$year}. Ready for payroll release.");
    }

    /**
     * Release approved Christmas Bonus batch to payroll
     */
    public function releaseChristmasBonus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bonus_year' => ['required', 'integer', 'min:2020', 'max:2099'],
        ]);

        $year = (int) $validated['bonus_year'];
        $count = $this->christmasBonusService->releaseBatchToPayroll($year);

        return redirect()->route('benefits.christmas-bonus', ['year' => $year])
            ->with('status', "Released {$count} Christmas Bonus allocations to payroll for Year {$year}.");
    }

    /**
     * Update Christmas Bonus Policy Settings
     */
    public function updateChristmasBonusSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'christmas_bonus_amount' => ['required', 'numeric', 'min:0', 'max:50000'],
            'christmas_bonus_min_months' => ['required', 'integer', 'min:0', 'max:60'],
            'christmas_bonus_enabled' => ['nullable', 'boolean'],
        ]);

        CompanySetting::setValue('christmas_bonus_amount', (string) $validated['christmas_bonus_amount']);
        CompanySetting::setValue('christmas_bonus_min_months', (string) $validated['christmas_bonus_min_months']);
        CompanySetting::setValue('christmas_bonus_enabled', $request->has('christmas_bonus_enabled') ? '1' : '0');

        return redirect()->route('benefits.christmas-bonus')
            ->with('status', 'Christmas bonus parameters and qualification rules updated successfully.');
    }

    /**
     * Export Christmas Bonus Allocation Roster CSV
     */
    public function exportChristmasBonusCsv(Request $request): StreamedResponse
    {
        $year = (int) $request->query('year', (string) date('Y'));
        $departmentId = $request->query('department_id');

        $query = Employee::with(['department', 'christmasBonusDisbursements' => fn ($q) => $q->where('bonus_year', $year)])
            ->where('employment_status', '!=', 'terminated')
            ->orderBy('first_name');

        if ($departmentId && $departmentId !== 'all') {
            $query->department($departmentId);
        }

        $employees = $query->get();
        $standardBonus = (float) CompanySetting::getValue('christmas_bonus_amount', 2000.00);
        $minMonths = (int) CompanySetting::getValue('christmas_bonus_min_months', 6);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Christmas_Bonus_Allocation_Year_' . $year . '_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($employees, $year, $standardBonus, $minMonths) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'Employee Code',
                'Employee Name',
                'Department',
                'Position',
                'Hire Date',
                'Tenure (Months)',
                'Qualification Status',
                'Pro-Rated',
                'Bonus Allocation (PHP)',
                'Disbursement Status',
            ]);

            foreach ($employees as $emp) {
                $disb = $emp->christmasBonusDisbursements->first();
                if ($disb) {
                    $tenureMonths = $disb->months_tenure;
                    $isProrated = $disb->is_prorated ? 'YES' : 'NO';
                    $allocated = $disb->calculated_bonus_amount;
                    $status = strtoupper($disb->status);
                    $qualStatus = $allocated > 0 ? ($disb->is_prorated ? 'PRO-RATED' : 'QUALIFIED') : 'PROBATIONARY';
                } else {
                    $calc = $this->christmasBonusService->calculateForEmployee($emp, $year, $standardBonus, $minMonths);
                    $tenureMonths = $calc['months_tenure'];
                    $isProrated = $calc['is_prorated'] ? 'YES' : 'NO';
                    $allocated = $calc['calculated_bonus_amount'];
                    $status = 'UNPROCESSED';
                    $qualStatus = $calc['is_qualified'] ? ($calc['is_prorated'] ? 'PRO-RATED' : 'QUALIFIED') : 'PROBATIONARY';
                }

                fputcsv($handle, [
                    $emp->employee_code,
                    "{$emp->first_name} {$emp->last_name}",
                    $emp->department?->name ?? 'General Fleet',
                    $emp->position,
                    $emp->hire_date ? $emp->hire_date->format('Y-m-d') : 'N/A',
                    $tenureMonths,
                    $qualStatus,
                    $isProrated,
                    number_format($allocated, 2, '.', ''),
                    $status,
                ]);
            }

            fclose($handle);
        }, 'Christmas_Bonus_Allocation_Year_' . $year . '.csv', $headers);
    }

    /**
     * Update All Benefits Company Settings (SIL, Meal Allowance, Christmas Bonus)
     */
    public function updateAllSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sil_annual_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'meal_allowance_daily' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'meal_allowance_schedule' => ['nullable', 'string', 'max:255'],
            'christmas_bonus_amount' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'christmas_bonus_min_months' => ['nullable', 'integer', 'min:0', 'max:60'],
            'redirect_tab' => ['nullable', 'string'],
        ]);

        if (isset($validated['sil_annual_days'])) {
            CompanySetting::setValue('sil_annual_days', (int) $validated['sil_annual_days'], 'Annual SIL entitlement days');
        }

        if (isset($validated['meal_allowance_daily'])) {
            CompanySetting::setValue('meal_allowance_daily', (float) $validated['meal_allowance_daily'], 'Daily meal allowance rate (PHP)');
        }

        if (isset($validated['meal_allowance_schedule'])) {
            CompanySetting::setValue('meal_allowance_schedule', (string) $validated['meal_allowance_schedule'], 'Meal allowance distribution schedule');
        }

        if (isset($validated['christmas_bonus_amount'])) {
            CompanySetting::setValue('christmas_bonus_amount', (float) $validated['christmas_bonus_amount'], 'Company fixed Christmas bonus benchmark amount (PHP)');
        }

        if (isset($validated['christmas_bonus_min_months'])) {
            CompanySetting::setValue('christmas_bonus_min_months', (int) $validated['christmas_bonus_min_months'], 'Minimum tenure (months) required to qualify for Christmas bonus');
        }

        return redirect()->back()->with('status', 'Company benefit policies successfully updated in database settings.')->with('success', 'Company benefit policies successfully updated.');
    }
}
