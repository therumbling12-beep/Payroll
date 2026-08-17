<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\CompensationController;
use App\Http\Controllers\EssController;
use App\Http\Controllers\HmoController;
use App\Http\Controllers\PayrollController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ─────────────────────────────────────────────────────────────
// Super Admin Secret Login Route (Option A — URL-only access)
// Access URL: /login
// NOTE: Authentication logic will be wired up per team's system.
// ─────────────────────────────────────────────────────────────
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// POST login route
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    $user = User::where('email', $credentials['email'])->first();
    if ($user && ($user->password === $credentials['password'] || Hash::check($credentials['password'], $user->password))) {
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
})->name('login.post');

// Dashboard Route — protect with your team's middleware when ready
Route::get('/dashboard', function () {
    // TODO: Add auth middleware once your login system is set up.
    // ->middleware('auth')
    return view('dashboard');
})->name('dashboard');

// Passenger Booking App Simulator (Team 10)
Route::get('/passenger-booking-app', function () {
    return view('passenger-app');
})->name('passenger.booking-app');

// Team 8 Facilities & Admin Sub-Modules and Integration Matrix Overview
Route::get('/facilities-admin', function () {
    return view('facilities.dashboard');
})->name('facilities.dashboard');

// Team 4 Compensation Planning Sub-Modules (Sections 2.2 to 2.16)
Route::prefix('compensation')->name('compensation.')->group(function () {
    // Section 2.2 Salary Band Management & 5-Factor Determination (§6.2, §6.3)
    Route::get('/salary-bands', [CompensationController::class, 'salaryBands'])->name('salary-bands');
    Route::post('/salary-bands/{grade}/update', [CompensationController::class, 'updateSalaryBand'])->name('salary-bands.update');
    Route::post('/salary-bands/bulk-adjust', [CompensationController::class, 'bulkAdjustBands'])->name('salary-bands.bulk-adjust');
    Route::post('/api/salary-determination', [CompensationController::class, 'determineSalary'])->name('salary-determination');

    // Sections 2.3, 2.6 & 2.13 Counter Offers & Multi-Level Approvals (§6.6, §6.9, §10.1, §13)
    Route::get('/counter-offers', [CompensationController::class, 'counterOffers'])->name('counter-offers');
    Route::post('/counter-offers/{adjustment}/response', [CompensationController::class, 'updateResponse'])->name('counter-offers.response');
    Route::post('/adjustments', [CompensationController::class, 'storeAdjustment'])->name('adjustments.store');
    Route::post('/adjustments/{adjustment}/admin-approve', [CompensationController::class, 'adminApproveAdjustment'])->name('adjustments.admin-approve');
    Route::post('/adjustments/{adjustment}/approve', [CompensationController::class, 'approveAdjustment'])->name('adjustments.approve');
    Route::post('/adjustments/{adjustment}/reject', [CompensationController::class, 'rejectAdjustment'])->name('adjustments.reject');
    Route::post('/adjustments/{adjustment}/response', [CompensationController::class, 'updateResponse'])->name('adjustments.response');
    Route::post('/api/finance-budget-validation', [CompensationController::class, 'validateFinanceBudget'])->name('finance.validate');
    Route::post('/api/counter-offer-calculator', [CompensationController::class, 'calculateCounterOffer'])->name('counter-offers.calculate');
    Route::post('/api/simulate-growth', [CompensationController::class, 'simulateCompensation'])->name('simulate');

    // Sections 2.9 & 2.10 Merit & Promotions Planning (§6.4, §6.7, §6.8)
    Route::get('/merit-promotions', [CompensationController::class, 'meritPromotions'])->name('merit-promotions');
    Route::post('/merit-promotions/complete', [CompensationController::class, 'completeMeritPlanning'])->name('merit-promotions.complete');
    Route::post('/api/merit-calculator', [CompensationController::class, 'calculateMeritProposal'])->name('merit-promotions.calculate');
    Route::post('/api/retroactive-calculator', [CompensationController::class, 'calculateRetroactivePay'])->name('retroactive.calculate');

    // Section 2.11 Bonus Allocation (§6.10)
    Route::get('/bonus-allocation', [CompensationController::class, 'bonusAllocation'])->name('bonus-allocation');
    Route::post('/bonus-allocation/store', [CompensationController::class, 'storeBonusAllocation'])->name('bonus-allocation.store');
    Route::post('/api/bonus-pool-calculator', [CompensationController::class, 'calculateBonusDistribution'])->name('bonus-allocation.calculate');

    // Section 2.12 Tenure Step Process (§6.5)
    Route::get('/tenure-steps', [CompensationController::class, 'tenureSteps'])->name('tenure-steps');
    Route::post('/tenure-steps/steps', [CompensationController::class, 'storeSalaryStep'])->name('tenure-steps.store-step');
    Route::post('/tenure-steps/{employee}/apply', [CompensationController::class, 'applyStep'])->name('tenure-steps.apply');
    Route::post('/tenure-steps/{employee}/hold', [CompensationController::class, 'holdStep'])->name('tenure-steps.hold');
    Route::post('/api/tenure-calculator', [CompensationController::class, 'calculateTenureStep'])->name('tenure-steps.calculate');

    // Section 2.8 Probationary to Regular Conversion (§6.8, DOLE Art. 296)
    Route::get('/probationary', [CompensationController::class, 'probationary'])->name('probationary');
    Route::post('/probationary/{employee}/regularize', [CompensationController::class, 'regularize'])->name('probationary.regularize');
    Route::post('/api/probationary-calculator', [CompensationController::class, 'calculateProbationaryConversion'])->name('probationary.calculate');

    // Section 2.16 Audit Trail & Compliance Log
    Route::get('/audit-trail', [CompensationController::class, 'auditTrail'])->name('audit-trail');
    Route::get('/audit-trail/export', [CompensationController::class, 'exportAuditTrail'])->name('audit-trail.export');
});


// Team 4 Payroll Management Sub-Modules (Database & Controller Driven)
Route::prefix('payroll')->name('payroll.')->group(function () {
    Route::get('/salary-computation', [PayrollController::class, 'cutoffsList'])->name('salary-computation');
    Route::get('/salary-computation/{cutoff}', [PayrollController::class, 'salaryComputation'])->name('salary-computation.show');
    Route::post('/batch-compute', [PayrollController::class, 'batchCompute'])->name('batch-compute');
    Route::post('/manual-compute', [PayrollController::class, 'storeManual'])->name('manual-compute');

    // Workflow State Machine Transitions
    Route::post('/workflow/{cutoff}/submit-admin', [PayrollController::class, 'submitToAdmin'])->name('workflow.submit-admin');
    Route::post('/workflow/{cutoff}/approve-admin', [PayrollController::class, 'approveByAdmin'])->name('workflow.approve-admin');
    Route::post('/workflow/{cutoff}/request-budget', [PayrollController::class, 'requestBudget'])->name('workflow.request-budget');
    Route::post('/workflow/{cutoff}/receive-budget', [PayrollController::class, 'markBudgetReceived'])->name('workflow.receive-budget');
    Route::post('/workflow/{cutoff}/release', [PayrollController::class, 'releasePayroll'])->name('workflow.release');

    Route::get('/payslips', [PayrollController::class, 'payslips'])->name('payslips');
    Route::get('/payslips/{computation}', [PayrollController::class, 'showPayslip'])->name('payslips.show');
    Route::get('/computations/{computation}/transparency', [PayrollController::class, 'computationTransparency'])->name('computations.transparency');
    Route::get('/payslips/batch/{cutoff}', [PayrollController::class, 'printBatchPayslips'])->name('payslips.batch');
    Route::post('/payslips/{cutoff}/push-ess', [PayrollController::class, 'pushPayslipsToEss'])->name('payslips.push-ess');
    Route::get('/13th-month', [PayrollController::class, 'thirteenthMonth'])->name('thirteenth-month');
    Route::get('/13th-month/{year}/employee/{employee}/transparency', [PayrollController::class, 'thirteenthMonthTransparency'])->name('thirteenth-month.transparency');
    Route::post('/13th-month/compute', [PayrollController::class, 'computeThirteenthMonth'])->name('thirteenth-month.compute');
    Route::post('/13th-month/workflow/{year}/submit-admin', [PayrollController::class, 'submitThirteenthMonthAdmin'])->name('thirteenth-month.workflow.submit-admin');
    Route::post('/13th-month/workflow/{year}/approve-admin', [PayrollController::class, 'approveThirteenthMonthAdmin'])->name('thirteenth-month.workflow.approve-admin');
    Route::post('/13th-month/workflow/{year}/request-budget', [PayrollController::class, 'requestThirteenthMonthBudget'])->name('thirteenth-month.workflow.request-budget');
    Route::post('/13th-month/workflow/{year}/receive-budget', [PayrollController::class, 'markThirteenthMonthBudgetReceived'])->name('thirteenth-month.workflow.receive-budget');
    Route::post('/13th-month/workflow/{year}/release', [PayrollController::class, 'releaseThirteenthMonth'])->name('thirteenth-month.workflow.release');
    Route::get('/reports', [PayrollController::class, 'reports'])->name('reports');
    Route::get('/export/{cutoff}/register', [PayrollController::class, 'exportPayrollRegister'])->name('export.register');
    Route::get('/export/{cutoff}/sss', [PayrollController::class, 'exportSssRemittance'])->name('export.sss');
    Route::get('/export/{cutoff}/philhealth', [PayrollController::class, 'exportPhilHealthRemittance'])->name('export.philhealth');
    Route::get('/export/{cutoff}/pagibig', [PayrollController::class, 'exportPagIbigRemittance'])->name('export.pagibig');
    Route::get('/export/{cutoff}/security-bank', [PayrollController::class, 'exportSecurityBankFile'])->name('export.security-bank');
    Route::get('/export/{cutoff}/cash-voucher', [PayrollController::class, 'exportCashVoucher'])->name('export.cash-voucher');
    Route::get('/export/{year}/alphalist', [PayrollController::class, 'exportBirAlphalist'])->name('export.alphalist');
    Route::get('/payment-modes', [PayrollController::class, 'paymentModes'])->name('payment-modes');
    Route::get('/audit-trail', [PayrollController::class, 'auditTrail'])->name('audit-trail');

    // Loan Amortization Management (Phase 3)
    Route::get('/loans', [PayrollController::class, 'loansIndex'])->name('loans');
    Route::post('/loans', [PayrollController::class, 'storeLoan'])->name('loans.store');
    Route::post('/loans/{loan}/pause', [PayrollController::class, 'pauseLoan'])->name('loans.pause');
    Route::post('/loans/{loan}/resume', [PayrollController::class, 'resumeLoan'])->name('loans.resume');

    // Off-Cycle Payroll & Final Pay Settlements (Phase 5)
    Route::get('/off-cycle', [PayrollController::class, 'offCycleIndex'])->name('off-cycle');
    Route::post('/off-cycle', [PayrollController::class, 'storeOffCycle'])->name('off-cycle.store');
    Route::get('/off-cycle/{offCycle}', [PayrollController::class, 'showOffCycle'])->name('off-cycle.show');
    Route::post('/off-cycle/{offCycle}/approve', [PayrollController::class, 'approveOffCycle'])->name('off-cycle.approve');
    Route::post('/off-cycle/{offCycle}/release', [PayrollController::class, 'releaseOffCycle'])->name('off-cycle.release');
    Route::get('/off-cycle/{offCycle}/export', [PayrollController::class, 'exportOffCycleCsv'])->name('off-cycle.export');
    Route::get('/off-cycle/items/{item}/certificate', [PayrollController::class, 'settlementCertificate'])->name('off-cycle.certificate');
    Route::get('/off-cycle/items/{item}/transparency', [PayrollController::class, 'offCycleItemTransparency'])->name('off-cycle.item-transparency');
});

// Team 4 Claims & Reimbursement Sub-Modules (v2.md §3.1 – §3.11)
Route::prefix('claims')->name('claims.')->group(function () {
    Route::get('/expenses', [ClaimController::class, 'expenses'])->name('expenses');
    Route::post('/expenses/fuel', [ClaimController::class, 'storeFuelClaim'])->name('expenses.fuel');
    Route::post('/expenses/operational', [ClaimController::class, 'storeOperationalExpense'])->name('expenses.operational');
    Route::get('/incentives', [ClaimController::class, 'incentives'])->name('incentives');
    Route::post('/incentives/batch-qualify', [ClaimController::class, 'batchQualifyIncentives'])->name('incentives.batch-qualify');
    Route::get('/maternity-leave', [ClaimController::class, 'maternityLeave'])->name('maternity-leave');
    Route::post('/maternity/store', [ClaimController::class, 'storeMaternityClaim'])->name('maternity.store');
    Route::post('/maternity/{claim}/sss-status', [ClaimController::class, 'updateSssStatus'])->name('maternity.sss-status');
    Route::post('/medical/store', [ClaimController::class, 'storeMedicalClaim'])->name('medical.store');
    Route::post('/{claim}/action', [ClaimController::class, 'workflowAction'])->name('workflow-action');
    Route::post('/batch-workflow', [ClaimController::class, 'batchWorkflow'])->name('batch-workflow');
    Route::post('/sync-payroll', [ClaimController::class, 'syncPayroll'])->name('sync-payroll');
    Route::get('/categories', [ClaimController::class, 'categories'])->name('categories');
    Route::post('/categories', [ClaimController::class, 'storeCategory'])->name('categories.store');
    Route::post('/categories/{category}/toggle', [ClaimController::class, 'toggleCategory'])->name('categories.toggle');
    Route::post('/categories/{category}/update', [ClaimController::class, 'updateCategory'])->name('categories.update');
    Route::post('/settings', [ClaimController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/reset', [ClaimController::class, 'resetPolicySettings'])->name('settings.reset');

    Route::get('/reports', [ClaimController::class, 'reports'])->name('reports');
    Route::get('/export', [ClaimController::class, 'export'])->name('export');
});

// Team 4 HMO & Benefits Administration Sub-Modules
Route::prefix('hmo-benefits')->name('hmo.')->group(function () {
    Route::get('/plans', [HmoController::class, 'plans'])->name('plans');
    Route::get('/enrollments', [HmoController::class, 'enrollments'])->name('enrollments');
    Route::post('/enrollments/sync-payroll', [HmoController::class, 'syncPayrollDeductions'])->name('enrollments.sync-payroll');
    Route::post('/enrollments/{enrollment}/deactivate', [HmoController::class, 'deactivateEnrollment'])->name('enrollments.deactivate');
    Route::post('/plans/enroll', [HmoController::class, 'enroll'])->name('enroll');
    Route::post('/plans/{enrollment}/update', [HmoController::class, 'updateEnrollment'])->name('update-enrollment');
    Route::post('/plans/log-utilization', [HmoController::class, 'logUtilization'])->name('log-utilization');
    Route::post('/plans/config', [HmoController::class, 'updateHmoConfig'])->name('plans.config');
    Route::post('/plans/config/reset', [HmoController::class, 'resetHmoConfiguration'])->name('plans.config.reset');
    Route::post('/facilities', [HmoController::class, 'storeFacility'])->name('facilities.store');
    Route::get('/plans/export-roster', [HmoController::class, 'exportRoster'])->name('plans.export-roster');
    Route::get('/plans/export-plans', [HmoController::class, 'exportPlans'])->name('export-plans');
    Route::post('/api/mbl-lookup', [HmoController::class, 'apiCalculateGradeMbl'])->name('api.mbl-lookup');
    Route::post('/enrollments/{enrollment}/hr-validate', [HmoController::class, 'validateEnrollmentHr'])->name('enrollments.hr-validate');
    Route::post('/enrollments/{enrollment}/request-budget', [HmoController::class, 'requestEnrollmentBudget'])->name('enrollments.request-budget');
    Route::post('/enrollments/{enrollment}/activate', [HmoController::class, 'activateEnrollment'])->name('enrollments.activate');
    Route::post('/enrollments/{enrollment}/reject', [HmoController::class, 'rejectEnrollment'])->name('enrollments.reject');
    Route::post('/enrollments/{enrollment}/renew', [HmoController::class, 'renewEnrollment'])->name('enrollments.renew');

    Route::get('/driver-insurance', [HmoController::class, 'driverInsurance'])->name('driver-insurance');
    Route::get('/driver-insurance/export-ledger', [HmoController::class, 'exportPoolLedger'])->name('driver-insurance.export-ledger');
    Route::post('/driver-insurance/claim', [HmoController::class, 'fileClaim'])->name('file-claim');
    Route::post('/driver-insurance/claim/{claim}/approve-hr', [HmoController::class, 'accidentClaimApproveHr'])->name('claim.approve-hr');
    Route::post('/driver-insurance/claim/{claim}/approve-admin', [HmoController::class, 'accidentClaimApproveAdmin'])->name('claim.approve-admin');
    Route::post('/driver-insurance/claim/{claim}/approve-finance', [HmoController::class, 'accidentClaimApproveFinance'])->name('claim.approve-finance');
    Route::post('/driver-insurance/claim/{claim}/return', [HmoController::class, 'accidentClaimReturn'])->name('claim.return');
    Route::post('/driver-insurance/contribution-rate', [HmoController::class, 'updateDriverContributionRate'])->name('update-contribution-rate');

    Route::get('/benefit-types', [HmoController::class, 'benefitTypes'])->name('benefit-types');
    Route::post('/benefit-types', [HmoController::class, 'storeBenefitType'])->name('store-benefit-type');
    Route::post('/benefit-types/{benefitType}/toggle', [HmoController::class, 'toggleBenefitType'])->name('toggle-benefit-type');

    Route::get('/cost-tracking', [HmoController::class, 'costTracking'])->name('cost-tracking');
    Route::get('/cost-tracking/export-tce', [HmoController::class, 'exportTceCsv'])->name('cost-tracking.export-tce');

    Route::get('/budget-requests', [HmoController::class, 'budgetRequests'])->name('budget-requests');
    Route::post('/budget-requests', [HmoController::class, 'submitRequest'])->name('submit-request');
    Route::post('/budget-requests/{requisition}/status', [HmoController::class, 'updateBudgetRequestStatus'])->name('update-budget-status');

    Route::get('/corporate-wellness', [HmoController::class, 'corporateWellness'])->name('corporate-wellness');
    Route::post('/ape/schedule', [HmoController::class, 'scheduleApe'])->name('ape.schedule');
    Route::post('/ape/batch-schedule', [HmoController::class, 'batchScheduleApe'])->name('ape.batch-schedule');
    Route::post('/ape/{exam}/record-results', [HmoController::class, 'recordApeResults'])->name('ape.record-results');
    Route::post('/group-life/enroll', [HmoController::class, 'enrollGroupLife'])->name('group-life.enroll');
    Route::post('/group-life/{policy}/update', [HmoController::class, 'updateGroupLife'])->name('group-life.update');
});

// Team 4 HR Analytics Dashboard Sub-Modules
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
    Route::get('/payroll', [AnalyticsController::class, 'payroll'])->name('payroll');
    Route::get('/budget', [AnalyticsController::class, 'budget'])->name('budget');
    Route::get('/overview', [AnalyticsController::class, 'overview'])->name('overview');
});

// Team 4 Employee Self-Service (ESS) Portal
Route::prefix('ess')->name('ess.')->group(function () {
    Route::get('/dashboard', [EssController::class, 'index'])->name('dashboard');
    Route::post('/claims/submit', [EssController::class, 'submitClaim'])->name('claims.submit');
    Route::post('/hmo/apply', [EssController::class, 'applyHmo'])->name('hmo.apply');
    Route::get('/hmo/card', [EssController::class, 'digitalCard'])->name('hmo.card');
    Route::post('/loa/request', [EssController::class, 'requestEmergencyLoa'])->name('loa.request');
    Route::post('/ape/schedule', [EssController::class, 'scheduleApe'])->name('ape.schedule');
    Route::post('/life/beneficiaries', [EssController::class, 'updateLifeBeneficiaries'])->name('life.beneficiaries');
    Route::post('/bank-details', [EssController::class, 'updateBankDetails'])->name('bank-details');
});

// Logout Route
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');
