<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth')->group(function (): void {
    Route::post('requests', [ServiceRequestController::class, 'store']);
    Route::get('requests', [ServiceRequestController::class, 'index']);
    Route::get('requests/{id}', [ServiceRequestController::class, 'show'])->whereNumber('id');
    Route::get('requests/{id}/history', [ServiceRequestController::class, 'history'])->whereNumber('id');
});
