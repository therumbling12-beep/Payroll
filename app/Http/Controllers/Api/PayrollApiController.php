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
            return response()->json(['error' => 'No salary record found for driver'], 44);
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

        $validated['employee_code'] = 'EMP-' . rand(1000, 9999);
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
}
