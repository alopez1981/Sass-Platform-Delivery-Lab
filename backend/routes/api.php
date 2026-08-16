<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FeatureFlagController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RequestController;
use Illuminate\Support\Facades\Route;

// Unauthenticated on purpose: orchestrators and uptime monitors don't have
// (and shouldn't need) a session. None of these leak sensitive details.
Route::get('/health/live', [HealthController::class, 'live']);
Route::get('/health/ready', [HealthController::class, 'ready']);
Route::get('/health/app', [HealthController::class, 'app']);

// 5 attempts/minute per IP — a basic brute-force mitigation on the one
// endpoint anyone can hit without already being authenticated.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests/{operationalRequest}', [RequestController::class, 'show']);
    Route::patch('/requests/{operationalRequest}/status', [RequestController::class, 'updateStatus']);

    Route::post('/requests/{operationalRequest}/comments', [CommentController::class, 'store']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    Route::get('/feature-flags', [FeatureFlagController::class, 'index']);
    Route::patch('/feature-flags/{key}', [FeatureFlagController::class, 'update']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
});
