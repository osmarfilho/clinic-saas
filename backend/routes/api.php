<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ClinicNotificationController;
use App\Http\Controllers\Api\ClinicSettingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FinancialTransactionController;
use App\Http\Controllers\Api\PatientController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', DashboardController::class);
    Route::post('patients/restore/{patient}', [PatientController::class, 'restore']);
    Route::apiResource('patients', PatientController::class);
    Route::apiResource('appointments', AppointmentController::class);
    Route::apiResource('financial-transactions', FinancialTransactionController::class);
    Route::get('settings', [ClinicSettingController::class, 'show']);
    Route::put('settings', [ClinicSettingController::class, 'update']);
    Route::get('notifications', [ClinicNotificationController::class, 'index']);
    Route::post('notifications/read-all', [ClinicNotificationController::class, 'markAllAsRead']);
    Route::post('notifications/{notification}/read', [ClinicNotificationController::class, 'markAsRead']);
});
