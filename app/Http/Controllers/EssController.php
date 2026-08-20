<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EssClaimSubmissionRequest;
use App\Http\Requests\SubmitBankAccountRequest;
use App\Models\BankAccountSubmission;
use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;
use App\Services\Benefits\ChristmasBonusService;
use App\Services\Benefits\DriverInsurancePoolService;
use App\Services\Benefits\ServiceIncentiveLeaveService;
use App\Services\Claims\ClaimTaxabilityService;
use App\Services\Claims\DuplicateClaimDetectionService;
use App\Services\Claims\FuelReimbursementValidationService;
use App\Services\Claims\MaternityBenefitService;
use App\Services\Claims\MedicalAssistanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EssController extends Controller
{
    public function __construct(
        protected FuelReimbursementValidationService $fuelService,
        protected ClaimTaxabilityService $taxabilityService,
        protected MaternityBenefitService $maternityService,
        protected MedicalAssistanceService $medicalService,
        protected DuplicateClaimDetectionService $duplicateService,
        protected DriverInsurancePoolService $driverPoolService,
        protected ServiceIncentiveLeaveService $silService,
        protected ChristmasBonusService $christmasBonusService
    ) {}

    /**
     * Employee Self-Service (ESS) Portal Dashboard
     */
    public function index(Request $request): View
    {
        $employeeId = $request->query('employee_id');
        $employees = Employee::orderBy('first_name')->get();
        
        $selectedEmployee = $employeeId ? Employee::find($employeeId) : $employees->first();

        $latestComputation = null;
        $claims = collect();
        $medicalUtilized = 0.00;
        $medicalCap = (float) CompanySetting::getValue('medical_cash_allowance_annual_cap', 10000.00);
        $maternityCalculations = [];
        $maternityTypes = MaternityBenefitService::MATERNITY_TYPES;
        $silRecord = null;
        $christmasBonusProjection = null;
        $driverPoolHistory = null;

        if ($selectedEmployee) {
            $latestComputation = SalaryComputation::where('employee_id', $selectedEmployee->id)->latest()->first();
            $claims = Claim::with(['categoryModel'])
                ->where('employee_id', $selectedEmployee->id)
                ->latest()
                ->take(20)
                ->get();

            $medicalUtilized = $this->taxabilityService->getEmployeeMedicalUtilizedThisYear($selectedEmployee);

            $maternityCalculations = [
                'normal_caesarean' => $this->maternityService->computeMaternityBenefit($selectedEmployee, 'normal_caesarean'),
                'solo_parent' => $this->maternityService->computeMaternityBenefit($selectedEmployee, 'solo_parent'),
                'miscarriage_emergency' => $this->maternityService->computeMaternityBenefit($selectedEmployee, 'miscarriage_emergency'),
            ];

            $silRecord = $this->silService->getOrCreateAnnualRecord($selectedEmployee, (int) date('Y'));
            $christmasBonusProjection = $this->christmasBonusService->calculateForEmployee($selectedEmployee, (int) date('Y'));
            $bankSubmission = BankAccountSubmission::where('employee_id', $selectedEmployee->id)->latest()->first();

            if (str_contains(strtolower($selectedEmployee->position ?? ''), 'driver') || str_contains(strtolower($selectedEmployee->department?->name ?? ''), 'fleet')) {
                $driverPoolHistory = $this->driverPoolService->getDriverContributionHistory($selectedEmployee);
            }
        }

        $categories = ClaimCategory::where('is_active', true)->orderBy('type')->orderBy('name')->get();
        $fuelSettings = [
            'pump_price' => $this->fuelService->getDefaultPumpPrice(),
            'efficiency' => $this->fuelService->getDefaultEfficiency(),
            'tolerance_pct' => $this->fuelService->getTolerancePercentage(),
        ];

        return view('ess.dashboard', compact(
            'employees',
            'selectedEmployee',
            'latestComputation',
            'claims',
            'categories',
            'fuelSettings',
            'medicalUtilized',
            'medicalCap',
            'maternityCalculations',
            'maternityTypes',
            'silRecord',
            'christmasBonusProjection',
            'driverPoolHistory',
            'bankSubmission'
        ));
    }

    /**
     * Submit Security Bank Account Details & Photo Proof via ESS
     */
    public function submitBankAccount(SubmitBankAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employee = Employee::findOrFail($validated['employee_id']);

        $attachmentPath = null;
        if ($request->hasFile('proof_document')) {
            $attachmentPath = $request->file('proof_document')->store('bank_proofs', 'public');
        }

        $submission = BankAccountSubmission::create([
            'employee_id' => $employee->id,
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'proof_attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        PayrollAuditTrail::create([
            'action' => 'ESS_BANK_ACCOUNT_SUBMITTED',
            'model_type' => BankAccountSubmission::class,
            'model_id' => $submission->id,
            'user_name' => $employee->first_name . ' ' . $employee->last_name,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'old_values' => [],
            'new_values' => [
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['account_number'],
                'has_proof' => (bool) $attachmentPath,
            ],
        ]);

        return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
            ->with('status', 'Your Security Bank account details and ATM proof have been submitted to HR for verification.');
    }

    /**
     * Employee Bank / Payment Method Details Setup (Legacy Support)
     */
    public function updateBankDetails(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'payment_method' => 'required|string|in:cash,bank',
            'bank_name' => 'nullable|required_if:payment_method,bank|string|max:255',
            'bank_account_number' => 'nullable|required_if:payment_method,bank|string|max:255',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $employee->update([
            'payment_method' => $validated['payment_method'],
            'payment_mode' => $validated['payment_method'],
            'bank_name' => $validated['bank_name'],
            'bank_account_number' => $validated['bank_account_number'],
        ]);

        return redirect()->back()->with('status', 'Employee bank deposit & payment details updated successfully!');
    }

    /**
     * Submit an Employee or Driver Self-Service Claim with Receipt
     */
    public function submitClaim(EssClaimSubmissionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employee = Employee::findOrFail($validated['employee_id']);
        $type = (string) $validated['type'];

        // Delegate Statutory Maternity Benefit Advance
        if ($type === 'maternity') {
            $maternityClaim = $this->maternityService->fileMaternityClaim($validated, $request->file('receipt_file'));
            $this->duplicateService->flagClaimIfDuplicate($maternityClaim);

            return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
                ->with('status', "Your RA 11210 Statutory Maternity Benefit Advance application [{$maternityClaim->receipt_number}] for PHP " . number_format((float)$maternityClaim->amount, 2) . " has been submitted to HR for validation.");
        }

        // Delegate Medical Assistance Reimbursement
        if ($type === 'medical') {
            $medicalClaim = $this->medicalService->fileMedicalClaim($validated, $request->file('receipt_file'));
            $this->duplicateService->flagClaimIfDuplicate($medicalClaim);

            return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
                ->with('status', "Your medical assistance claim [{$medicalClaim->receipt_number}] for PHP " . number_format((float)$medicalClaim->amount, 2) . " has been submitted to HR for validation.");
        }

        // Delegate Driver Road Incident / Accident Relief Claim
        if ($type === 'accident') {
            $accidentData = [
                'employee_id' => $employee->id,
                'incident_type' => $validated['description'] ? 'Road Incident' : 'Work Injury',
                'incident_date' => $validated['incident_date'] ?? $validated['expense_date'],
                'bill_amount' => (float) $validated['amount'],
                'description' => $validated['description'] ?? 'Driver road incident relief claim filed via ESS portal',
                'vehicle_plate_number' => $validated['incident_location'] ?? null,
                'diagnosis' => $validated['hospital_name'] ?? null,
            ];

            $accidentClaim = $this->driverPoolService->fileClaim(
                $employee->id,
                $accidentData,
                $request->file('receipt_file'),
                $request->file('receipt_file'),
                $request->file('receipt_file')
            );

            return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
                ->with('status', "Your Driver Emergency Road Incident Relief claim [{$accidentClaim->incident_number}] for PHP " . number_format((float)$accidentClaim->bill_amount, 2) . " has been submitted to HR Team 4.");
        }

        // Standard Expense Reimbursement
        $amount = (float) $validated['amount'];
        $receiptNumber = (string) $validated['receipt_number'];
        $expenseDate = (string) $validated['expense_date'];
        $description = $validated['description'] ?? null;
        $category = null;
        $categoryId = ! empty($validated['category_id']) ? (int) $validated['category_id'] : null;

        $attachmentPath = null;
        if ($request->hasFile('receipt_file')) {
            $attachmentPath = $request->file('receipt_file')->store('attachments', 'public');
        } else {
            $attachmentPath = 'attachments/' . strtolower($type) . '-receipt.pdf';
        }

        $nonTaxable = $amount;
        $taxable = 0.00;
        $autoValidated = false;
        $fuelVariancePct = null;
        $distanceKm = ! empty($validated['distance_traveled_km']) ? (float) $validated['distance_traveled_km'] : null;
        $fuelPrice = ! empty($validated['fuel_pump_price']) ? (float) $validated['fuel_pump_price'] : null;
        $fuelEfficiency = ! empty($validated['vehicle_fuel_efficiency_kpl']) ? (float) $validated['vehicle_fuel_efficiency_kpl'] : null;

        if ($categoryId) {
            $catModel = ClaimCategory::find($categoryId);
            if ($catModel) {
                $category = $catModel->name;
                $taxResult = $this->taxabilityService->classifyClaim($catModel, $amount, $employee);
                $nonTaxable = (float) $taxResult['non_taxable_amount'];
                $taxable = (float) $taxResult['taxable_amount'];
            }
        }

        if ($distanceKm && $distanceKm > 0) {
            $fuelResult = $this->fuelService->validateFuelClaim($amount, $distanceKm, $fuelEfficiency, $fuelPrice);
            $autoValidated = (bool) $fuelResult['is_within_tolerance'];
            $fuelVariancePct = (float) $fuelResult['variance_pct'];
        }

        $claim = Claim::create([
            'employee_id' => $employee->id,
            'category_id' => $categoryId,
            'category' => $category ?: ucfirst($type) . ' Claim',
            'type' => $type,
            'amount' => $amount,
            'non_taxable_amount' => $nonTaxable,
            'taxable_amount' => $taxable,
            'description' => $description ?: "Self-service {$type} claim filed via ESS portal",
            'receipt_number' => $receiptNumber,
            'merchant_name' => $validated['merchant_name'] ?? null,
            'status' => 'pending',
            'approval_status' => 'pending_hr',
            'expense_date' => $expenseDate,
            'attachment_path' => $attachmentPath,
            'distance_traveled_km' => $distanceKm,
            'fuel_pump_price' => $fuelPrice,
            'vehicle_fuel_efficiency_kpl' => $fuelEfficiency,
            'fuel_variance_pct' => $fuelVariancePct,
            'auto_validated' => $autoValidated,
            'effective_date' => now(),
        ]);

        $this->duplicateService->flagClaimIfDuplicate($claim);

        $auditAction = 'ESS_CLAIM_SUBMITTED';

        PayrollAuditTrail::create([
            'action' => $auditAction,
            'model_type' => Claim::class,
            'model_id' => $claim->id,
            'user_name' => $employee->first_name . ' ' . $employee->last_name,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'old_values' => [],
            'new_values' => [
                'claim_id' => $claim->id,
                'receipt_number' => $receiptNumber,
                'amount' => $amount,
                'type' => $type,
                'non_taxable_amount' => $nonTaxable,
                'taxable_amount' => $taxable,
                'auto_validated' => $autoValidated,
            ],
        ]);

        return redirect()->route('ess.dashboard', ['employee_id' => $employee->id])
            ->with('status', "Your claim [{$receiptNumber}] for PHP " . number_format($amount, 2) . " has been submitted to HR for validation.");
    }
}
