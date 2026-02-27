<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TravelOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/travel-orders', [TravelOrderController::class, 'index'])->name('travel-orders.index');
        Route::post('/travel-orders', [TravelOrderController::class, 'store'])->name('travel-orders.store');
        Route::get('/travel-orders/{travelOrder}', [TravelOrderController::class, 'show'])->name('travel-orders.show');
        Route::patch('/travel-orders/{travelOrder}/status', [TravelOrderController::class, 'updateStatus'])->name('travel-orders.update-status');
        Route::patch('/travel-orders/{travelOrder}/cancel', [TravelOrderController::class, 'cancel'])->name('travel-orders.cancel');
    });
});
