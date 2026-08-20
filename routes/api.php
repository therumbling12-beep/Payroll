<?php

use App\Http\Controllers\Api\PayrollApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes for Team 4 Payroll Management
|--------------------------------------------------------------------------
*/

Route::prefix('payroll')->middleware('api')->group(function () {
    
    // Outbound REST APIs (Data Provisioning to External Teams)
    Route::get('/compliance-review', [PayrollApiController::class, 'complianceReview']);
    Route::get('/disbursements', [PayrollApiController::class, 'disbursements']);
    Route::get('/driver-earnings/{driver_id}', [PayrollApiController::class, 'driverEarnings']);

    // Inbound REST Webhooks (Receiving Payload Data from External Teams)
    Route::prefix('webhooks')->group(function () {
        Route::post('/new-hire', [PayrollApiController::class, 'webhookNewHire']);
        Route::post('/attendance', [PayrollApiController::class, 'webhookAttendance']);
        Route::post('/bonus', [PayrollApiController::class, 'webhookBonus']);
        Route::post('/trip-income', [PayrollApiController::class, 'webhookTripIncome']);
        Route::post('/legal-decision', [PayrollApiController::class, 'webhookLegalDecision']);
        Route::post('/counter-offer', [PayrollApiController::class, 'webhookCounterOffer']);
        Route::post('/merit-promotion', [PayrollApiController::class, 'webhookTeam3Merit']);
        Route::post('/team3-promotion', [\App\Http\Controllers\CompensationController::class, 'syncTeam3Promotion'])->name('api.webhooks.team3.promotions');
        Route::post('/driver-reimbursement', [PayrollApiController::class, 'webhookDriverReimbursement']);
    });

});

Route::post('/v1/integrations/team3/promotions', [\App\Http\Controllers\CompensationController::class, 'syncTeam3Promotion'])
    ->name('api.integrations.team3.promotions');

