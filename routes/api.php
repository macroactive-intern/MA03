<?php

use App\Http\Controllers\Api\CoachClientCheckInController;
use App\Http\Controllers\CheckInController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/check-ins', [CheckInController::class, 'store']);
    Route::get('/check-ins/streak', [CheckInController::class, 'streak']);

    Route::middleware('coach')->prefix('coach/clients')->group(function () {
        Route::get('/{user}/check-ins', [CoachClientCheckInController::class, 'index']);
        Route::get('/{user}/streak', [CoachClientCheckInController::class, 'streak']);
    });
});
