<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\ServiceRequestWorkflowController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public endpoint: users must log in before accessing protected endpoints.
Route::post('/login', [AuthController::class, 'login']);

// Protected endpoints.
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response()->json([
            'data' => $request->user(),
        ]);
    });

    // Service request CRUD
    Route::apiResource(
        'service-requests',
        ServiceRequestController::class
    );

    // Service request workflow
    Route::post(
        '/service-requests/{serviceRequest}/submit',
        [ServiceRequestWorkflowController::class, 'submit']
    );

    Route::post(
        '/service-requests/{serviceRequest}/decision',
        [ServiceRequestWorkflowController::class, 'decide']
    );

    Route::post(
        '/service-requests/{serviceRequest}/assign',
        [ServiceRequestWorkflowController::class, 'assign']
    );

    Route::post(
        '/service-requests/{serviceRequest}/start',
        [ServiceRequestWorkflowController::class, 'start']
    );

    Route::post(
        '/service-requests/{serviceRequest}/complete',
        [ServiceRequestWorkflowController::class, 'complete']
    );

    // Comments
    Route::post(
        '/service-requests/{serviceRequest}/comments',
        [CommentController::class, 'store']
    );
});
