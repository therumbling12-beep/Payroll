<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\CompensationController;
use App\Http\Controllers\HmoController;
use App\Http\Controllers\PayrollController;
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

// Placeholder POST route — teams will replace this with their own auth logic
Route::post('/login', function () {
    // TODO: Wire up your team's authentication logic here.
    // Example using Laravel Auth:
    //   $credentials = request()->validate(['email' => 'required|email', 'password' => 'required']);
    //   if (Auth::attempt($credentials, request()->boolean('remember'))) {
    //       return redirect()->intended('/dashboard');
    //   }
    //   return back()->withErrors(['email' => 'Invalid credentials.']);
    return back();
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

// Team 4 Compensation Planning Sub-Modules
Route::prefix('compensation')->name('compensation.')->group(function () {
    Route::get('/salary-config', [CompensationController::class, 'salaryConfig'])->name('salary-config');
    Route::get('/counter-offers', [CompensationController::class, 'counterOffers'])->name('counter-offers');
    Route::get('/merit-promotions', [CompensationController::class, 'meritPromotions'])->name('merit-promotions');
    Route::post('/adjustments', [CompensationController::class, 'storeAdjustment'])->name('adjustments.store');
    Route::post('/adjustments/{adjustment}/approve', [CompensationController::class, 'approveAdjustment'])->name('adjustments.approve');
    Route::post('/adjustments/{adjustment}/reject', [CompensationController::class, 'rejectAdjustment'])->name('adjustments.reject');
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
    Route::get('/13th-month', [PayrollController::class, 'thirteenthMonth'])->name('thirteenth-month');
    Route::post('/13th-month/compute', [PayrollController::class, 'computeThirteenthMonth'])->name('thirteenth-month.compute');
    Route::post('/13th-month/workflow/{year}/submit-admin', [PayrollController::class, 'submitThirteenthMonthAdmin'])->name('thirteenth-month.workflow.submit-admin');
    Route::post('/13th-month/workflow/{year}/approve-admin', [PayrollController::class, 'approveThirteenthMonthAdmin'])->name('thirteenth-month.workflow.approve-admin');
    Route::post('/13th-month/workflow/{year}/request-budget', [PayrollController::class, 'requestThirteenthMonthBudget'])->name('thirteenth-month.workflow.request-budget');
    Route::post('/13th-month/workflow/{year}/receive-budget', [PayrollController::class, 'markThirteenthMonthBudgetReceived'])->name('thirteenth-month.workflow.receive-budget');
    Route::post('/13th-month/workflow/{year}/release', [PayrollController::class, 'releaseThirteenthMonth'])->name('thirteenth-month.workflow.release');
    Route::get('/payment-modes', [PayrollController::class, 'paymentModes'])->name('payment-modes');
    Route::get('/audit-trail', [PayrollController::class, 'auditTrail'])->name('audit-trail');
});

// Team 4 Claims & Reimbursement Sub-Modules
Route::prefix('claims')->name('claims.')->group(function () {
    Route::get('/expenses', [ClaimController::class, 'expenses'])->name('expenses');
    Route::get('/incentives', [ClaimController::class, 'incentives'])->name('incentives');
    Route::get('/maternity-leave', [ClaimController::class, 'maternityLeave'])->name('maternity-leave');
    Route::post('/', [ClaimController::class, 'store'])->name('store');
    Route::post('/{claim}/approve', [ClaimController::class, 'approve'])->name('approve');
    Route::post('/{claim}/reject', [ClaimController::class, 'reject'])->name('reject');
});

// Team 4 HMO & Benefits Administration Sub-Modules
Route::prefix('hmo-benefits')->name('hmo.')->group(function () {
    Route::get('/plans', [HmoController::class, 'plans'])->name('plans');
    Route::post('/plans/enroll', [HmoController::class, 'enroll'])->name('enroll');
    
    Route::get('/driver-insurance', [HmoController::class, 'driverInsurance'])->name('driver-insurance');
    Route::post('/driver-insurance/claim', [HmoController::class, 'fileClaim'])->name('file-claim');
    
    Route::get('/budget-requests', [HmoController::class, 'budgetRequests'])->name('budget-requests');
    Route::post('/budget-requests', [HmoController::class, 'submitRequest'])->name('submit-request');
});

// Team 4 HR Analytics Dashboard Sub-Modules
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
    Route::get('/payroll', [AnalyticsController::class, 'payroll'])->name('payroll');
    Route::get('/budget', [AnalyticsController::class, 'budget'])->name('budget');
});



// Logout Route — implement when auth is set up
Route::post('/logout', function () {
    // TODO: Auth::logout(); session()->invalidate(); session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');
