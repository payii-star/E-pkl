<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\Api\InternController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AdminAttendanceController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\FaceController;
use App\Http\Controllers\Api\AdminFaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================================================
// AUTHENTICATION
// ============================================================================

Route::middleware(['auth', 'json'])->prefix('auth')->group(function () {

    Route::post('register', [AuthController::class, 'register'])
        ->withoutMiddleware('auth');

    Route::post('register-with-face', [AuthController::class, 'registerWithFace'])
        ->withoutMiddleware('auth');

    Route::post('login', [AuthController::class, 'login'])
        ->withoutMiddleware('auth');

    Route::delete('logout', [AuthController::class, 'logout']);

    Route::get('me', [AuthController::class, 'me']);
});


// ============================================================================
// SETTING - PUBLIC READ
// ============================================================================

Route::prefix('setting')->group(function () {
    Route::get('', [SettingController::class, 'index']);
});


// ============================================================================
// FACE RECOGNITION - PUBLIC
// ============================================================================
//
// GET  /api/face/profiles
// POST /api/face/login
//
// Catatan:
// Untuk sementara recognition masih dilakukan di browser.
// Nanti production sebaiknya dipindahkan ke Python service + liveness.
// ============================================================================

Route::prefix('face')->group(function () {

    Route::get('profiles', [FaceController::class, 'profiles']);

    Route::post('login', [FaceController::class, 'login']);
});


// ============================================================================
// AUTHENTICATED API
// ============================================================================

Route::middleware(['auth', 'json'])->group(function () {

    // ------------------------------------------------------------------------
    // PROFILE
    // ------------------------------------------------------------------------

    Route::post('/profile', [UserController::class, 'updateProfile']);

    Route::delete('/profile', [UserController::class, 'deleteProfile']);

    Route::post('/profile/change-email', [
        UserController::class,
        'changeEmail'
    ]);

    Route::post('/profile/change-password', [
        UserController::class,
        'changePassword'
    ]);


    // ------------------------------------------------------------------------
    // SETTING
    // ------------------------------------------------------------------------

    Route::prefix('setting')
        ->middleware('can:setting')
        ->group(function () {

            Route::post('', [SettingController::class, 'update']);
        });


    // ------------------------------------------------------------------------
    // MASTER USER & ROLE
    // ------------------------------------------------------------------------

    Route::prefix('master')->group(function () {

        Route::apiResource('users', UserController::class);

        Route::middleware('can:master-role')->group(function () {

            Route::get('roles', [
                RoleController::class,
                'get'
            ])->withoutMiddleware('can:master-role');

            Route::post('roles', [
                RoleController::class,
                'index'
            ]);

            Route::post('roles/store', [
                RoleController::class,
                'store'
            ]);

            Route::apiResource('roles', RoleController::class)
                ->except(['index', 'store']);
        });
    });


    // ========================================================================
    // INTERN / MAGANG
    // ========================================================================

    Route::prefix('intern')->group(function () {

        Route::get('estimation', [
            InternController::class,
            'estimation'
        ]);

        Route::get('tasks', [
            TaskController::class,
            'index'
        ]);
    });


    // ========================================================================
    // TASKS
    // ========================================================================

    Route::prefix('tasks')->group(function () {

        Route::post('', [
            TaskController::class,
            'store'
        ]);

        Route::patch('{task}/status', [
            TaskController::class,
            'updateStatus'
        ]);
    });


    // ========================================================================
    // JOURNALS
    // ========================================================================

    Route::prefix('journals')->group(function () {

        // GET /api/journals
        Route::get('', [
            JournalController::class,
            'index'
        ]);

        // POST /api/journals
        Route::post('', [
            JournalController::class,
            'store'
        ]);

        // GET /api/journals/history
        Route::get('history', [
            JournalController::class,
            'history'
        ]);

        // GET /api/journals/pending-approval
        Route::get('pending-approval', [
            JournalController::class,
            'pendingApproval'
        ]);

        // GET /api/journals/approval-history
        Route::get('approval-history', [
            JournalController::class,
            'approvalHistory'
        ]);

        // POST /api/journals/{journal}/approve
        Route::post('{journal}/approve', [
            JournalController::class,
            'approve'
        ]);

        // POST /api/journals/{journal}/reject
        Route::post('{journal}/reject', [
            JournalController::class,
            'reject'
        ]);
    });


    // ========================================================================
    // ADMIN DASHBOARD
    // ========================================================================

    Route::get('admin/summary', [
        AdminDashboardController::class,
        'summary'
    ])->middleware('role:hr-admin');


    // ========================================================================
    // ADMIN ATTENDANCE
    // ========================================================================

    Route::prefix('admin/attendance')
        ->middleware('role:hr-admin')
        ->group(function () {
            Route::get('interns', [
                AdminAttendanceController::class,
                'interns'
            ]);

            Route::get('{intern}', [
                AdminAttendanceController::class,
                'recap'
            ]);
        });


    // ========================================================================
    // ATTENDANCE
    // ========================================================================

    Route::prefix('attendances')->group(function () {

        // GET /api/attendances
        Route::get('', [
            AttendanceController::class,
            'index'
        ]);

        // GET /api/attendances/today
        Route::get('today', [
            AttendanceController::class,
            'today'
        ]);

        // POST /api/attendances/check-in
        Route::post('check-in', [
            AttendanceController::class,
            'checkIn'
        ]);

        // POST /api/attendances/check-out
        Route::post('check-out', [
            AttendanceController::class,
            'checkOut'
        ]);
    });


    // ========================================================================
    // FACE REGISTER
    // ========================================================================

    Route::post('face/register', [
        FaceController::class,
        'register'
    ]);


    // ========================================================================
    // ADMIN FACE MANAGEMENT
    // ========================================================================

    Route::prefix('admin/face')
        ->middleware('role:hr-admin|atasan')
        ->group(function () {

            Route::get('interns', [
                AdminFaceController::class,
                'internList'
            ]);

            Route::post('register/{user}', [
                AdminFaceController::class,
                'registerForIntern'
            ]);

            Route::delete('{user}', [
                AdminFaceController::class,
                'deleteProfile'
            ]);

            Route::post('impersonate/{user}', [
                AdminFaceController::class,
                'impersonate'
            ]);
        });
});