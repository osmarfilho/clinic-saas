<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ClinicNotificationController;
use App\Http\Controllers\Api\ClinicSettingController;
use App\Http\Controllers\Api\SuperAdmin\ClinicController as SuperAdminClinicController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FinancialTransactionController;
use App\Http\Controllers\Api\PatientController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', DashboardController::class)->middleware('can:view dashboard');
    Route::get('patients', [PatientController::class, 'index'])->middleware('can:view patients');
    Route::post('patients', [PatientController::class, 'store'])->middleware('can:manage patients');
    Route::get('patients/{patient}', [PatientController::class, 'show'])->middleware('can:view patients');
    Route::put('patients/{patient}', [PatientController::class, 'update'])->middleware('can:manage patients');
    Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->middleware('can:manage patients');
    Route::post('patients/restore/{patient}', [PatientController::class, 'restore'])->middleware('can:manage patients');
    Route::apiResource('appointments', AppointmentController::class)->middleware('can:manage appointments');
    Route::apiResource('financial-transactions', FinancialTransactionController::class)->middleware('can:access finance');
    Route::get('settings', [ClinicSettingController::class, 'show'])->middleware('can:manage clinic settings');
    Route::put('settings', [ClinicSettingController::class, 'update'])->middleware('can:manage clinic settings');
    Route::get('notifications', [ClinicNotificationController::class, 'index'])->middleware('can:view notifications');
    Route::post('notifications/read-all', [ClinicNotificationController::class, 'markAllAsRead'])->middleware('can:view notifications');
    Route::post('notifications/{notification}/read', [ClinicNotificationController::class, 'markAsRead'])->middleware('can:view notifications');
});

Route::middleware(['auth:sanctum', 'can:manage clinics'])
    ->prefix('super-admin/clinics')
    ->group(function () {
        Route::get('/', [SuperAdminClinicController::class, 'index']);
        Route::post('/', [SuperAdminClinicController::class, 'store']);
        Route::put('/{clinic}', [SuperAdminClinicController::class, 'update']);
        Route::patch('/{clinic}/activate', [SuperAdminClinicController::class, 'activate']);
        Route::patch('/{clinic}/deactivate', [SuperAdminClinicController::class, 'deactivate']);
    });
