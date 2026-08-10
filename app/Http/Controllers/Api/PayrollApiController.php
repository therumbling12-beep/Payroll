<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\SalaryComputationResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PerformanceBonus;
use App\Models\SalaryComputation;
use App\Models\TripIncome;
use App\Services\PayrollEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class PayrollApiController extends Controller
{
    public function __construct(
        protected PayrollEngineService $payrollEngine
    ) {}

    /**
     * Outbound API for Team 8 (Legal Compliance Dashboard)
     * Provides payroll records awaiting legal review & approval.
     */
    public function complianceReview(): AnonymousResourceCollection
    {
        $pending = SalaryComputation::with('employee.department')
            ->where('status', 'pending_approval')
            ->get();

        return SalaryComputationResource::collection($pending);
    }

    /**
     * Outbound API for Team 5 (Financial)
     * Provides disbursement totals ONLY after Team 8 approves.
     */
    public function disbursements(): JsonResponse
    {
        $approved = SalaryComputation::with('employee')
            ->where('status', 'approved_legal')
            ->get();

        $totalPayout = $approved->sum('net_pay');

        return response()->json([
            'status' => 'success',
            'team_target' => 'Team 5 Financial Management',
            'approved_count' => $approved->count(),
            'total_disbursement_amount' => $totalPayout,
            'records' => SalaryComputationResource::collection($approved),
        ]);
    }

    /**
     * Outbound API for Team 9 (Driver Companion App)
     * Provides driver net earnings breakdown.
     */
    public function driverEarnings(int $driverId): JsonResponse|SalaryComputationResource
    {
        $computation = SalaryComputation::with('employee')
            ->where('employee_id', $driverId)
            ->latest()
            ->first();

        if (!$computation) {
            return response()->json(['error' => 'No salary record found for driver'], 404);
        }

        return new SalaryComputationResource($computation);
    }

    /**
     * Inbound Webhook: Team 1 (New Hire Record)
     */
    public function webhookNewHire(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:employees,email',
            'position' => 'required|string',
            'daily_rate' => 'nullable|numeric',
            'monthly_rate' => 'nullable|numeric',
        ]);

        $nextId = (Employee::max('id') ?? 0) + 1;
        $validated['employee_code'] = 'EMP-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
        $employee = Employee::create($validated);

        return response()->json([
            'message' => 'New hire ingested into Payroll Management successfully from Team 1',
            'data' => new EmployeeResource($employee),
        ], 201);
    }

    /**
     * Inbound Webhook: Team 2 (Approved Attendance)
     */
    public function webhookAttendance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'cutoff_period' => 'required|string',
            'days_worked' => 'required|integer',
            'lates_count' => 'nullable|integer',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'cutoff_period' => $validated['cutoff_period']],
            ['days_worked' => $validated['days_worked'], 'lates_count' => $validated['lates_count'] ?? 0]
        );

        // Auto-recalculate salary
        $employee = Employee::find($validated['employee_id']);
        $computation = $this->payrollEngine->computeForEmployee($employee, $validated['cutoff_period']);

        return response()->json([
            'message' => 'Attendance ingested & salary auto-computed successfully from Team 2',
            'computation' => new SalaryComputationResource($computation),
        ]);
    }

    /**
     * Inbound Webhook: Team 3 (Performance Bonus)
     */
    public function webhookBonus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'cutoff_period' => 'required|string',
            'bonus_amount' => 'required|numeric',
            'reason' => 'nullable|string',
        ]);

        $bonus = PerformanceBonus::create($validated);

        // Recalculate salary
        $employee = Employee::find($validated['employee_id']);
        $computation = $this->payrollEngine->computeForEmployee($employee, $validated['cutoff_period']);

        return response()->json([
            'message' => 'Performance bonus ingested from Team 3',
            'computation' => new SalaryComputationResource($computation),
        ]);
    }

    /**
     * Inbound Webhook: Team 9 (Driver Trip Income)
     */
    public function webhookTripIncome(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'cutoff_period' => 'required|string',
            'total_trips' => 'required|integer',
            'total_trip_earnings' => 'required|numeric',
        ]);

        $income = TripIncome::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'cutoff_period' => $validated['cutoff_period']],
            ['total_trips' => $validated['total_trips'], 'total_trip_earnings' => $validated['total_trip_earnings']]
        );

        // Recalculate salary
        $employee = Employee::find($validated['employee_id']);
        $computation = $this->payrollEngine->computeForEmployee($employee, $validated['cutoff_period']);

        return response()->json([
            'message' => 'Driver trip earnings ingested from Team 9',
            'computation' => new SalaryComputationResource($computation),
        ]);
    }

    /**
     * Inbound Webhook: Team 8 (Legal Decision: Approve/Reject)
     */
    public function webhookLegalDecision(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'salary_computation_id' => 'required|exists:salary_computations,id',
            'decision' => 'required|in:approved_legal,rejected',
        ]);

        $computation = SalaryComputation::findOrFail($validated['salary_computation_id']);
        $computation->update(['status' => $validated['decision']]);

        return response()->json([
            'message' => "Payroll record decision updated to '{$validated['decision']}' from Team 8 Legal Compliance",
            'computation' => new SalaryComputationResource($computation),
        ]);
    }

    /**
     * Inbound Webhook: Team 1 (Applicant Management Counter-Offer Request)
     */
    public function webhookCounterOffer(Request $request, \App\Services\CompensationService $compensationService): JsonResponse
    {
        $validated = $request->validate([
            'position' => 'required|string',
            'years_experience' => 'required|integer|min:0',
            'certifications_count' => 'nullable|integer|min:0',
        ]);

        $result = $compensationService->computeCounterOffer(
            $validated['position'],
            $validated['years_experience'],
            $validated['certifications_count'] ?? 0
        );

        return response()->json([
            'message' => 'Automated credential-based counter offer computed successfully for Team 1',
            'data' => $result,
        ]);
    }

    /**
     * Inbound Webhook: Team 3 (Performance Data & Merit Promotion Trigger)
     */
    public function webhookTeam3Merit(Request $request, \App\Services\CompensationService $compensationService, \App\Services\FinancialService $financialService): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'kpi_score' => 'required|numeric|min:0|max:100',
            'years_of_service' => 'required|integer|min:1',
            'recommended_bump_percent' => 'nullable|numeric|min:0',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $oldRate = $employee->monthly_rate ?: ($employee->daily_rate * 26);

        $bumpPercent = $validated['recommended_bump_percent'] ?? ($validated['kpi_score'] >= 90 ? 10.0 : 5.0);
        $newRate = round($oldRate + ($oldRate * ($bumpPercent / 100)), 2);

        $budgetCheck = $financialService->checkBudgetAvailability($newRate, $employee->department->name ?? 'General');
        $status = $budgetCheck['approved'] ? 'pending' : 'rejected_financial_budget';

        $adjustment = \App\Models\CompensationAdjustment::create([
            'employee_id' => $employee->id,
            'type' => 'merit_promotion',
            'old_rate' => $oldRate,
            'new_rate' => $newRate,
            'bonus_amount' => $validated['kpi_score'] >= 95 ? 5000.00 : 0.00,
            'old_position' => $employee->position,
            'new_position' => $employee->position,
            'reason' => "Team 3 Ingested Appraisal (KPI: {$validated['kpi_score']}%, Service: {$validated['years_of_service']} yrs)",
            'status' => $status,
            'effective_date' => now(),
        ]);

        return response()->json([
            'message' => 'Team 3 Performance data ingested and Merit Promotion generated successfully',
            'data' => $adjustment,
            'budget_check' => $budgetCheck,
        ]);
    }

    /**
     * Inbound Webhook: Team 7 (Driver Work Experience - Reimbursement Request)
     */
    public function webhookDriverReimbursement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'receipt_number' => 'nullable|string',
            'cutoff_period' => 'nullable|string',
        ]);

        $claim = \App\Models\Claim::create([
            'employee_id' => $validated['employee_id'],
            'type' => 'expense',
            'amount' => $validated['amount'],
            'description' => '[Team 7 Driver Reimbursement] ' . $validated['description'],
            'receipt_number' => $validated['receipt_number'] ?? ('T7-' . strtoupper(Str::random(6))),
            'cutoff_period' => $validated['cutoff_period'] ?? '2026-07-01_15',
            'status' => 'pending',
            'effective_date' => now(),
        ]);

        return response()->json([
            'message' => 'Driver reimbursement request successfully received from Team 7 and queued for Payroll approval',
            'data' => $claim,
        ], 201);
    }
}
