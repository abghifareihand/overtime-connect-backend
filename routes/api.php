<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\api\CalculateController;
use App\Http\Controllers\Api\OvertimeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ✅ ROUTE UNTUK AUTHENTICATION
Route::post('/register', [AuthController::class, 'register']);  // Register user
Route::post('/login', [AuthController::class, 'login']);        // Login user

// ✅ ROUTE YANG MEMBUTUHKAN AUTH (Harus pakai token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']); // Get data user yang login
    Route::post('/logout', [AuthController::class, 'logout']); // Logout user

    // Update profile
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/photo', [AuthController::class, 'updatePhoto']);  // Update foto profil
    Route::post('/user/email', [AuthController::class, 'updateEmail']);  // Update email (harus masukkan password)
    Route::post('/user/salary', [AuthController::class, 'updateSalary']);  // Update email (harus masukkan password)
    Route::post('/user/password', [AuthController::class, 'updatePassword']);  // Update password (dengan validasi)

    // Overtime
    Route::post('/overtime', [OvertimeController::class, 'store']);
    Route::get('/overtime-years', [OvertimeController::class, 'getYears']);
    Route::get('/overtime-report', [OvertimeController::class, 'getReport']);
    Route::get('/overtime-report-weekly', [OvertimeController::class, 'getReportWeekly']);
    Route::get('/overtime-report-monthly', [OvertimeController::class, 'getReportMonthly']);
    Route::get('/overtime-report-yearly', [OvertimeController::class, 'getReportYearly']);

    // Calculate
    Route::post('/calculate', [CalculateController::class, 'store']);
    Route::get('/calculate', [CalculateController::class, 'getCalculate']);
    Route::get('/calculate/{id}', [CalculateController::class, 'getCalculateById']);
});
