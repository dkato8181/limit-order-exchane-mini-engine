<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/profile', function (Request $request) {
        $user = auth()->user()->load('assets');

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'balance', 'assets']),
        ]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});
