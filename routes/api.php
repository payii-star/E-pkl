<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\Api\InternController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\FaceController;            // ← BARU
use App\Http\Controllers\Api\AdminFaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication
Route::middleware(['auth', 'json'])->prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->withoutMiddleware('auth');
    Route::post('register-with-face', [AuthController::class, 'registerWithFace'])->withoutMiddleware('auth');
    Route::post('login', [AuthController::class, 'login'])->withoutMiddleware('auth');
    Route::delete('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
});

Route::prefix('setting')->group(function () {
    Route::get('', [SettingController::class, 'index']);
});

// ── Face Recognition (PUBLIC — diakses sebelum login) ────────────────────────
// GET  /face/profiles  → ambil semua face descriptor untuk pencocokan di Vue
// POST /face/login     → generate JWT setelah wajah cocok di client
Route::prefix('face')->group(function () {
    Route::get('profiles', [FaceController::class, 'profiles']);
    Route::post('login',   [FaceController::class, 'login']);
});
// ─────────────────────────────────────────────────────────────────────────────

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

    Route::get('admin/summary', [AdminDashboardController::class, 'summary'])->middleware('role:hr-admin');

    Route::prefix('attendances')->group(function () {
        Route::get('',        [AttendanceController::class, 'index']);
        Route::get('today',   [AttendanceController::class, 'today']);    // ← BARU
        Route::post('check-in',  [AttendanceController::class, 'checkIn']);
        Route::post('check-out', [AttendanceController::class, 'checkOut']);
    });

    // ── Face Profile (butuh login) ────────────────────────────────────────────
    // POST /face/register → daftar / update face profile sendiri
    Route::post('face/register', [FaceController::class, 'register']);

    // Admin Face Management
    Route::prefix('admin/face')->middleware('role:hr-admin|atasan')->group(function () {
        Route::get('interns', [AdminFaceController::class, 'internList']);
        Route::post('register/{user}', [AdminFaceController::class, 'registerForIntern']);
        Route::delete('{user}', [AdminFaceController::class, 'deleteProfile']);
        Route::post('impersonate/{user}', [AdminFaceController::class, 'impersonate']);
    });
    // ─────────────────────────────────────────────────────────────────────────
});