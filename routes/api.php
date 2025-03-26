<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\PasswordResetController;
use Illuminate\Support\Facades\Route;

// ✅ ROUTE UNTUK AUTHENTICATION
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ✅ ROUTE UNTUK FORGOT PASSWORD
Route::post('/request-otp', [PasswordResetController::class, 'requestOtp']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// ✅ ROUTE UNTUK CALCULATE
Route::post('/calculate-overtime', [OvertimeController::class, 'calculate']);


// ✅ ROUTE YANG MEMBUTUHKAN AUTH (Harus pakai token)
Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/email', [AuthController::class, 'updateEmail']);
    Route::post('/user/username', [AuthController::class, 'updateUsername']);
    Route::post('/user/salary', [AuthController::class, 'updateSalary']);
    Route::post('/user/password', [AuthController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Overtime
    Route::post('/overtime', [OvertimeController::class, 'store']);
    Route::delete('/overtime/{id}', [OvertimeController::class, 'destroy']);
    Route::get('/overtime-years', [OvertimeController::class, 'getYears']);
    Route::get('/overtime-report', [OvertimeController::class, 'getReport']);
    Route::get('/overtime-report/{id}', [OvertimeController::class, 'getReportById']);
    Route::get('/overtime-report/{id}/details', [OvertimeController::class, 'getOvertimeDetails']);
    Route::get('/overtime-report-weekly', [OvertimeController::class, 'getReportWeekly']);
    Route::get('/overtime-report-monthly', [OvertimeController::class, 'getReportMonthly']);
    Route::get('/overtime-report-yearly', [OvertimeController::class, 'getReportYearly']);
    Route::get('/overtime-report-date', [OvertimeController::class, 'getReportDate']);
});
