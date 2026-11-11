
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ServiceRequestWorkflowController;

Route::post('/login', [AuthController::class, 'login']);

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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response()->json([
            'data' => $request->user(),
        ]);
    });

    Route::apiResource(
        'service-requests',
        ServiceRequestController::class
    );
});
