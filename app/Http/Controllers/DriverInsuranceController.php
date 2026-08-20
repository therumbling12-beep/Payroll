<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DriverAccidentClaimRequest;
use App\Http\Requests\DriverPoolConfigRequest;
use App\Models\AccidentClaim;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Services\Benefits\DriverInsurancePoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriverInsuranceController extends Controller
{
    public function __construct(
        protected DriverInsurancePoolService $driverPoolService
    ) {}

    /**
     * Driver Accident Insurance Pool & Claims Governance Dashboard
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = AccidentClaim::with('employee.department')->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($sub) use ($search) {
                    $sub->search($search);
                })->orWhere('incident_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('vehicle_plate_number', 'like', "%{$search}%");
            });
        }

        if ($status && $status !== 'all') {
            $query->where('workflow_status', $status);
        }

        $accidentClaims = $query->paginate(10)->withQueryString();
        $employees = Employee::where('position', 'like', '%Driver%')->orderBy('first_name')->get();

        $stats = $this->driverPoolService->getPoolSummary();
        $ledger = $this->driverPoolService->getPoolLedger(null, 10);

        return view('payroll-benefits.driver-insurance.index', compact(
            'accidentClaims',
            'employees',
            'stats',
            'ledger',
            'search',
            'status'
        ));
    }

    /**
     * File a new driver accident claim with evidence files
     */
    public function fileClaim(DriverAccidentClaimRequest $request): RedirectResponse
    {
        $claim = $this->driverPoolService->fileClaim(
            (int) $request->validated('employee_id'),
            $request->validated(),
            $request->file('police_report'),
            $request->file('medical_receipt'),
            $request->file('incident_photo')
        );

        return redirect()->route('driver-insurance.index')->with('status', "Driver accident claim {$claim->incident_number} submitted for HR validation.");
    }

    /**
     * Step 1: HR Reviews & Validates Driver Accident Claim
     */
    public function accidentClaimApproveHr(Request $request, AccidentClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'min:0.01', 'max:' . $claim->bill_amount],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->driverPoolService->approveHr($claim, (float) $validated['approved_amount'], $validated['remarks'] ?? null);

        return redirect()->route('driver-insurance.index')->with('status', "Claim {$claim->incident_number} approved by HR and forwarded to Fleet Administrator.");
    }

    /**
     * Step 2: Fleet Administrator Reviews Driver Accident Claim
     */
    public function accidentClaimApproveAdmin(Request $request, AccidentClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->driverPoolService->approveAdmin($claim, $validated['remarks'] ?? null);

        return redirect()->route('driver-insurance.index')->with('status', "Claim {$claim->incident_number} approved by Admin and submitted to Financial Management.");
    }

    /**
     * Step 3: Finance Validates Fund Availability & Releases Driver Claim
     */
    public function accidentClaimApproveFinance(Request $request, AccidentClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->driverPoolService->approveFinance($claim, $validated['remarks'] ?? null);

        return redirect()->route('driver-insurance.index')->with('status', "Claim {$claim->incident_number} approved and payout disbursed from Driver Pool.");
    }

    /**
     * Return Driver Accident Claim at any step
     */
    public function accidentClaimReturn(Request $request, AccidentClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $this->driverPoolService->returnClaim($claim, $validated['remarks']);

        return redirect()->route('driver-insurance.index')->with('status', "Claim {$claim->incident_number} returned for revision with remarks.");
    }

    /**
     * Update driver benefit contribution rate and company match settings
     */
    public function updateDriverContributionRate(DriverPoolConfigRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $rate = (float) ($validated['contribution_rate'] ?? $validated['rate'] ?? 3.0);
        $match = (float) ($validated['company_match_pct'] ?? 50.0);

        CompanySetting::setValue('driver_benefit_contribution_rate', (string) ($rate / 100.0));
        CompanySetting::setValue('driver_pool_company_match_pct', (string) $match);

        return redirect()->route('driver-insurance.index')->with('status', "Driver insurance pool contribution rate updated to {$rate}% and company matching to {$match}%.");
    }

    /**
     * Export driver pool accounting ledger to CSV
     */
    public function exportPoolLedger(): StreamedResponse
    {
        return $this->driverPoolService->exportPoolLedgerCsv();
    }

    /**
     * View or retrieve individual driver contribution history and claim timeline
     */
    public function driverHistory(Request $request, Employee $employee): \Illuminate\Http\JsonResponse
    {
        $history = $this->driverPoolService->getDriverContributionHistory($employee);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => "{$employee->first_name} {$employee->last_name}",
                'code' => $employee->employee_code,
                'position' => $employee->position,
                'department' => $employee->department?->name ?? 'Logistics Fleet',
            ],
            'total_contributed' => $history['total_contributed'],
            'company_match_total' => $history['company_match_total'],
            'total_pool_credit' => $history['total_pool_credit'],
            'claims_count' => $history['claims_count'],
            'claims_disbursed_total' => $history['claims_disbursed_total'],
            'net_coverage_balance' => $history['net_coverage_balance'],
            'contributions' => $history['contributions']->map(fn ($c) => [
                'date' => $c->created_at->format('M j, Y'),
                'ref' => $c->reference_code,
                'desc' => $c->description,
                'amount' => number_format((float) $c->amount, 2),
            ]),
            'claims' => $history['claims']->map(fn ($cl) => [
                'incident_number' => $cl->incident_number,
                'incident_date' => $cl->incident_date ? $cl->incident_date->format('M j, Y') : 'N/A',
                'bill_amount' => number_format((float) $cl->bill_amount, 2),
                'approved_amount' => number_format((float) $cl->approved_amount, 2),
                'status' => $cl->workflow_status,
            ]),
        ]);
    }

    /**
     * Export periodic financial statement CSV for driver pool
     */
    public function exportStatement(Request $request): StreamedResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        return $this->driverPoolService->exportPeriodicStatementCsv($startDate, $endDate);
    }
}
