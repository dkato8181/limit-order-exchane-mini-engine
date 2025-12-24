<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', function (Request $request) {
        $user = $request->user()->load('assets');

        return $user->toResource();
    });

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::get('available-assets', [OrderController::class, 'availableAssets']);
    Route::post('trade/{id}/broadcast', [OrderController::class, 'broadcast']);
});
