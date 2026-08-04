<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\Api\InternController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication
Route::middleware(['auth', 'json'])->prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->withoutMiddleware('auth');
    Route::delete('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
});

Route::prefix('setting')->group(function () {
    Route::get('', [SettingController::class, 'index']);
});

Route::middleware(['auth', 'json'])->group(function () {

    // Profile
    Route::post('/profile', [UserController::class, 'updateProfile']);
    Route::post('/profile/change-email', [UserController::class, 'changeEmail']);
    Route::post('/profile/change-password', [UserController::class, 'changePassword']);

    Route::prefix('setting')->middleware('can:setting')->group(function () {
        Route::post('', [SettingController::class, 'update']);
    });

    // Master (user & role management)
    Route::prefix('master')->group(function () {
        Route::apiResource('users', UserController::class);

        Route::middleware('can:master-role')->group(function () {
            Route::get('roles', [RoleController::class, 'get'])->withoutMiddleware('can:master-role');
            Route::post('roles', [RoleController::class, 'index']);
            Route::post('roles/store', [RoleController::class, 'store']);
            Route::apiResource('roles', RoleController::class)->except(['index', 'store']);
        });
    });

    // ===== Modul HR / Magang =====

    Route::prefix('intern')->group(function () {
        Route::get('estimation', [InternController::class, 'estimation']);
        Route::get('tasks', [TaskController::class, 'index']);
    });

    Route::prefix('tasks')->group(function () {
        Route::post('', [TaskController::class, 'store']);
        Route::patch('{task}/status', [TaskController::class, 'updateStatus']);
    });

    Route::prefix('journals')->group(function () {
        Route::post('', [JournalController::class, 'store']);
        Route::get('history', [JournalController::class, 'history']);
        Route::get('pending-approval', [JournalController::class, 'pendingApproval']);
        Route::post('{journal}/approve', [JournalController::class, 'approve']);
        Route::post('{journal}/reject', [JournalController::class, 'reject']);
    });

    Route::prefix('attendances')->group(function () {
        Route::get('', [AttendanceController::class, 'index']);
        Route::post('check-in', [AttendanceController::class, 'checkIn']);
        Route::post('check-out', [AttendanceController::class, 'checkOut']);
    });
});