<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BenefitsController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\CompensationController;
use App\Http\Controllers\DriverInsuranceController;
use App\Http\Controllers\EssController;
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
    if ($user && Hash::check($credentials['password'], $user->password)) {
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
})->name('login.post');

// Dashboard Route — open access for evaluation & presentation
Route::get('/dashboard', function () {
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
    Route::post('/employees/{employee}/direct-merit', [CompensationController::class, 'applyDirectMeritIncrease'])->name('direct-merit');

    // Sections 2.3, 2.6 & 2.13 Counter Offers & Multi-Level Approvals (§6.6, §6.9, §10.1, §13)
    Route::get('/counter-offers', [CompensationController::class, 'counterOffers'])->name('counter-offers');
    Route::post('/counter-offers/{adjustment}/response', [CompensationController::class, 'updateResponse'])->name('counter-offers.response');
    Route::post('/adjustments', [CompensationController::class, 'storeAdjustment'])->name('adjustments.store');
    Route::post('/adjustments/{adjustment}/admin-approve', [CompensationController::class, 'adminApproveAdjustment'])->name('adjustments.admin-approve');
    Route::post('/adjustments/{adjustment}/approve', [CompensationController::class, 'approveAdjustment'])->name('adjustments.approve');
    Route::post('/adjustments/{adjustment}/reject', [CompensationController::class, 'rejectAdjustment'])->name('adjustments.reject');
    Route::post('/api/finance-budget-validation', [CompensationController::class, 'validateFinanceBudget'])->name('finance.validate');
    Route::post('/api/counter-offer-calculator', [CompensationController::class, 'calculateCounterOffer'])->name('counter-offers.calculate');

    // Sections 2.9 & 2.10 Merit & Promotions Planning (§6.4, §6.7, §6.8)
    Route::get('/merit-promotions', [CompensationController::class, 'meritPromotions'])->name('merit-promotions');
    Route::post('/merit-promotions/complete', [CompensationController::class, 'completeMeritPlanning'])->name('merit-promotions.complete');
    Route::post('/api/merit-calculator', [CompensationController::class, 'calculateMeritProposal'])->name('merit-promotions.calculate');
    Route::post('/api/retroactive-calculator', [CompensationController::class, 'calculateRetroactivePay'])->name('retroactive.calculate');

    // Section 2.11 Bonus Allocation — removed (docs/no.md: bonuses N/A, Phase 2)

    // Section 2.12 Tenure Step Process (§6.5)
    Route::get('/tenure-steps', [CompensationController::class, 'tenureSteps'])->name('tenure-steps');
    Route::post('/tenure-steps/steps', [CompensationController::class, 'storeSalaryStep'])->name('tenure-steps.store-step');
    Route::post('/tenure-steps/{employee}/apply', [CompensationController::class, 'applyStep'])->name('tenure-steps.apply');
    Route::post('/tenure-steps/{employee}/hold', [CompensationController::class, 'holdStep'])->name('tenure-steps.hold');
    Route::post('/api/tenure-calculator', [CompensationController::class, 'calculateTenureStep'])->name('tenure-steps.calculate');

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
    Route::post('/salary-computation/batch-update', [PayrollController::class, 'batchUpdateManual'])->name('salary-computation.batch-update');

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
    Route::post('/payment-modes/{employee}', [PayrollController::class, 'updatePaymentMode'])->name('payment-modes.update');
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
    Route::get('/incentives', fn () => redirect()->route('claims.expenses'))->name('incentives');
    Route::post('/incentives/batch-qualify', fn () => redirect()->route('claims.expenses'))->name('incentives.batch-qualify');
    Route::get('/maternity-leave', [ClaimController::class, 'maternityLeave'])->name('maternity-leave');
    Route::post('/maternity/{claim}/sss-status', [ClaimController::class, 'updateSssStatus'])->name('maternity.sss-status');
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

// Benefits Administration Sub-Modules (SIL, Meal Allowance, Christmas Bonus)
Route::prefix('benefits')->name('benefits.')->group(function () {
    Route::get('/', [BenefitsController::class, 'index'])->name('index');
    Route::post('/settings', [BenefitsController::class, 'updateAllSettings'])->name('settings.update');
    Route::get('/sil', [BenefitsController::class, 'sil'])->name('sil');
    Route::post('/sil/record', [BenefitsController::class, 'recordSil'])->name('sil.record');
    Route::post('/sil/convert-cash', [BenefitsController::class, 'convertSilCash'])->name('sil.convert-cash');
    Route::post('/sil/reset-year', [BenefitsController::class, 'resetSilYear'])->name('sil.reset-year');
    Route::get('/sil/export', [BenefitsController::class, 'exportSilCsv'])->name('sil.export');
    Route::post('/sil/settings', [BenefitsController::class, 'updateSilSettings'])->name('sil.settings');

    Route::get('/meal-allowance', [BenefitsController::class, 'mealAllowance'])->name('meal-allowance');
    Route::post('/meal-allowance/generate', [BenefitsController::class, 'generateMealDisbursements'])->name('meal-allowance.generate');
    Route::post('/meal-allowance/approve', [BenefitsController::class, 'approveMealDisbursements'])->name('meal-allowance.approve');
    Route::post('/meal-allowance/release', [BenefitsController::class, 'releaseMealDisbursements'])->name('meal-allowance.release');
    Route::post('/meal-allowance/settings', [BenefitsController::class, 'updateMealAllowanceSettings'])->name('meal-allowance.settings');
    Route::get('/meal-allowance/export', [BenefitsController::class, 'exportMealAllowanceCsv'])->name('meal-allowance.export');

    Route::get('/christmas-bonus', [BenefitsController::class, 'christmasBonus'])->name('christmas-bonus');
    Route::post('/christmas-bonus/generate', [BenefitsController::class, 'generateChristmasBonus'])->name('christmas-bonus.generate');
    Route::post('/christmas-bonus/approve', [BenefitsController::class, 'approveChristmasBonus'])->name('christmas-bonus.approve');
    Route::post('/christmas-bonus/release', [BenefitsController::class, 'releaseChristmasBonus'])->name('christmas-bonus.release');
    Route::post('/christmas-bonus/settings', [BenefitsController::class, 'updateChristmasBonusSettings'])->name('christmas-bonus.settings');
    Route::get('/christmas-bonus/export', [BenefitsController::class, 'exportChristmasBonusCsv'])->name('christmas-bonus.export');
});

// Standalone Driver Accident Insurance Pool (Preserved & Isolated from HMO)
Route::prefix('driver-insurance')->name('driver-insurance.')->group(function () {
    Route::get('/', [DriverInsuranceController::class, 'index'])->name('index');
    Route::get('/export-ledger', [DriverInsuranceController::class, 'exportPoolLedger'])->name('export-ledger');
    Route::get('/export-statement', [DriverInsuranceController::class, 'exportStatement'])->name('export-statement');
    Route::get('/driver/{employee}/history', [DriverInsuranceController::class, 'driverHistory'])->name('driver-history');
    Route::post('/claim', [DriverInsuranceController::class, 'fileClaim'])->name('file-claim');
    Route::post('/claim/{claim}/approve-hr', [DriverInsuranceController::class, 'accidentClaimApproveHr'])->name('claim.approve-hr');
    Route::post('/claim/{claim}/approve-admin', [DriverInsuranceController::class, 'accidentClaimApproveAdmin'])->name('claim.approve-admin');
    Route::post('/claim/{claim}/approve-finance', [DriverInsuranceController::class, 'accidentClaimApproveFinance'])->name('claim.approve-finance');
    Route::post('/claim/{claim}/return', [DriverInsuranceController::class, 'accidentClaimReturn'])->name('claim.return');
    Route::post('/contribution-rate', [DriverInsuranceController::class, 'updateDriverContributionRate'])->name('update-contribution-rate');
});

// Analytics Sub-Modules
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
    Route::get('/payroll', [AnalyticsController::class, 'payroll'])->name('payroll');
    Route::get('/budget', [AnalyticsController::class, 'budget'])->name('budget');
    Route::get('/overview', [AnalyticsController::class, 'overview'])->name('overview');
});

// Employee Self-Service (ESS) Portal
Route::prefix('ess')->name('ess.')->group(function () {
    Route::get('/dashboard', [EssController::class, 'index'])->name('dashboard');
    Route::post('/claims/submit', [EssController::class, 'submitClaim'])->name('claims.submit');
    Route::post('/bank-details', [EssController::class, 'updateBankDetails'])->name('bank-details');
});

// Logout Route
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');
