<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;

use App\Http\Controllers\Api\InternController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AdminAttendanceController;
use App\Http\Controllers\Api\AdminInternPeriodController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\FaceController;
use App\Http\Controllers\Api\AdminFaceController;
use App\Http\Controllers\Api\LandingProxyController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\LandingProjectController;
use App\Http\Controllers\Api\LandingServiceController;
use App\Http\Controllers\Api\LandingStatisticController;
use App\Http\Controllers\Api\LandingTeamController;
use App\Http\Controllers\Api\LandingTestimonialController;
use App\Http\Controllers\Api\LandingMenuController;
use App\Http\Controllers\Api\LandingFooterController;
use App\Http\Controllers\Api\LandingContentPageController;

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

Route::prefix('face')->group(function () {

    Route::get('profiles', [FaceController::class, 'profiles']);

    Route::post('login', [FaceController::class, 'login']);
});


// ============================================================================
// PUBLIC — dipanggil dari project Landing buat nampilin section-section-nya
// ============================================================================

Route::get('front/client-logos', [ClientController::class, 'publicIndex']);

Route::get('front/projects', [
    LandingProjectController::class,
    'publicIndex'
]);

Route::get('front/best-projects', [
    LandingProjectController::class,
    'publicFeatured'
]);

Route::get('front/services', [
    LandingServiceController::class,
    'publicIndex'
]);

Route::get('front/statistics', [
    LandingStatisticController::class,
    'publicIndex'
]);

Route::get('front/teams', [
    LandingTeamController::class,
    'publicIndex'
]);

Route::get('front/testimonials', [
    LandingTestimonialController::class,
    'publicIndex'
]);

Route::get('front/navbar', [
    LandingMenuController::class,
    'publicIndex'
]);

Route::get('footer/landing', [
    LandingFooterController::class,
    'publicIndex'
]);

Route::get('front/content', [
    LandingContentPageController::class,
    'publicIndex'
]);


// ============================================================================
// AUTHENTICATED API
// ============================================================================

Route::middleware(['auth', 'json'])->group(function () {

    // ------------------------------------------------------------------------
    // PROFILE
    // ------------------------------------------------------------------------

    Route::post('/profile', [
        UserController::class,
        'updateProfile'
    ]);

    Route::delete('/profile', [
        UserController::class,
        'deleteProfile'
    ]);

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

            Route::post('', [
                SettingController::class,
                'update'
            ]);
        });


    // ------------------------------------------------------------------------
    // MASTER USER, ROLE, & LANDING MANAGEMENT
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


        // --------------------------------------------------------------------
        // Client / Mitra ("Our Clients" section)
        // --------------------------------------------------------------------

        Route::middleware('permission:landing-management')
            ->prefix('client-logos')
            ->group(function () {

                Route::get('', [
                    ClientController::class,
                    'index'
                ]);

                Route::post('store', [
                    ClientController::class,
                    'store'
                ]);

                Route::post('reorder', [
                    ClientController::class,
                    'reorder'
                ]);

                Route::get('{clientLogo}', [
                    ClientController::class,
                    'show'
                ]);

                Route::put('{clientLogo}', [
                    ClientController::class,
                    'update'
                ]);

                Route::delete('{clientLogo}', [
                    ClientController::class,
                    'destroy'
                ]);
            });


        // --------------------------------------------------------------------
        // Projects
        // --------------------------------------------------------------------

        Route::middleware('permission:landing-management')
            ->prefix('projects')
            ->group(function () {

                Route::get('', [
                    LandingProjectController::class,
                    'adminIndex'
                ]);

                Route::post('', [
                    LandingProjectController::class,
                    'store'
                ]);

                Route::get('{project}', [
                    LandingProjectController::class,
                    'show'
                ]);

                // POST digunakan agar mendukung upload file saat update
                Route::post('{project}', [
                    LandingProjectController::class,
                    'update'
                ]);

                Route::delete('{project}', [
                    LandingProjectController::class,
                    'destroy'
                ]);
            });


        // --------------------------------------------------------------------
        // Statistics
        // --------------------------------------------------------------------

        Route::middleware('permission:landing-management')
            ->prefix('statistics')
            ->group(function () {

                Route::get('', [
                    LandingStatisticController::class,
                    'adminIndex'
                ]);

                Route::post('', [
                    LandingStatisticController::class,
                    'store'
                ]);

                Route::get('{statistic}', [
                    LandingStatisticController::class,
                    'show'
                ]);

                Route::put('{statistic}', [
                    LandingStatisticController::class,
                    'update'
                ]);

                Route::delete('{statistic}', [
                    LandingStatisticController::class,
                    'destroy'
                ]);
            });


        // --------------------------------------------------------------------
        // Menu
        // --------------------------------------------------------------------

        Route::middleware('permission:landing-management')
            ->prefix('menu')
            ->group(function () {

                Route::get('', [
                    LandingMenuController::class,
                    'adminIndex'
                ]);

                Route::post('', [
                    LandingMenuController::class,
                    'store'
                ]);

                Route::get('{menu}', [
                    LandingMenuController::class,
                    'show'
                ]);

                Route::put('{menu}', [
                    LandingMenuController::class,
                    'update'
                ]);

                Route::delete('{menu}', [
                    LandingMenuController::class,
                    'destroy'
                ]);
            });


        // --------------------------------------------------------------------
        // Services
        // --------------------------------------------------------------------

        Route::middleware('permission:landing-management')
            ->prefix('services')
            ->group(function () {

                Route::get('', [
                    LandingServiceController::class,
                    'adminIndex'
                ]);

                Route::post('', [
                    LandingServiceController::class,
                    'store'
                ]);

                Route::get('{service}', [
                    LandingServiceController::class,
                    'show'
                ]);

                Route::put('{service}', [
                    LandingServiceController::class,
                    'update'
                ]);

                Route::delete('{service}', [
                    LandingServiceController::class,
                    'destroy'
                ]);
            });


        // --------------------------------------------------------------------
        // Testimonials
        // --------------------------------------------------------------------

        Route::middleware('permission:landing-management')
            ->prefix('testimonials')
            ->group(function () {

                Route::get('', [
                    LandingTestimonialController::class,
                    'adminIndex'
                ]);

                Route::post('', [
                    LandingTestimonialController::class,
                    'store'
                ]);

                Route::get('{testimonial}', [
                    LandingTestimonialController::class,
                    'show'
                ]);

                Route::put('{testimonial}', [
                    LandingTestimonialController::class,
                    'update'
                ]);

                Route::delete('{testimonial}', [
                    LandingTestimonialController::class,
                    'destroy'
                ]);
            });


        // --------------------------------------------------------------------
        // Teams
        // --------------------------------------------------------------------

        Route::middleware('permission:landing-management')
            ->prefix('teams')
            ->group(function () {

                Route::get('', [
                    LandingTeamController::class,
                    'adminIndex'
                ]);

                Route::post('', [
                    LandingTeamController::class,
                    'store'
                ]);

                Route::get('{team}', [
                    LandingTeamController::class,
                    'show'
                ]);

                Route::put('{team}', [
                    LandingTeamController::class,
                    'update'
                ]);

                Route::delete('{team}', [
                    LandingTeamController::class,
                    'destroy'
                ]);
            });


        // --------------------------------------------------------------------
        // Footer
        // --------------------------------------------------------------------

        Route::middleware('permission:landing-management')
            ->prefix('footer')
            ->group(function () {

                Route::get('', [
                    LandingFooterController::class,
                    'adminIndex'
                ]);

                Route::post('', [
                    LandingFooterController::class,
                    'store'
                ]);

                Route::get('{footer}', [
                    LandingFooterController::class,
                    'show'
                ]);

                Route::put('{footer}', [
                    LandingFooterController::class,
                    'update'
                ]);

                Route::delete('{footer}', [
                    LandingFooterController::class,
                    'destroy'
                ]);
            });


        // --------------------------------------------------------------------
        // Landing Content
        // --------------------------------------------------------------------

        Route::middleware('permission:landing-management')
            ->prefix('landing-content')
            ->group(function () {

                Route::get('', [
                    LandingContentPageController::class,
                    'adminIndex'
                ]);

                Route::post('', [
                    LandingContentPageController::class,
                    'store'
                ]);

                Route::get('{contentPage}', [
                    LandingContentPageController::class,
                    'show'
                ]);

                Route::put('{contentPage}', [
                    LandingContentPageController::class,
                    'update'
                ]);

                Route::delete('{contentPage}', [
                    LandingContentPageController::class,
                    'destroy'
                ]);
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

    // Hanya hr-admin yang boleh membuat/melihat semua/menghapus tugas.
    // Sebelumnya route POST '' di sini TIDAK dijaga permission apapun — bug
    // keamanan: intern manapun bisa memberi tugas ke user lain lewat API
    // langsung. Sekarang dipindah ke prefix admin/tasks + permission khusus.
    Route::prefix('admin/tasks')
        ->middleware('permission:task-management')
        ->group(function () {
            Route::get('', [TaskController::class, 'adminIndex']);
            Route::post('', [TaskController::class, 'store']);
            Route::delete('{task}', [TaskController::class, 'destroy']);
            Route::post('{task}/review', [TaskController::class, 'review']);
        });

    Route::prefix('tasks')->group(function () {

        Route::patch('{task}/status', [
            TaskController::class,
            'updateStatus'
        ]);

        Route::post('{task}/submit', [TaskController::class, 'submit']);
    });


    // ========================================================================
    // IZIN TIDAK MASUK (leave requests)
    // ========================================================================

    // User (intern/atasan) ajukan izin & lihat riwayat izinnya sendiri
    Route::prefix('leave-requests')->group(function () {
        Route::get('', [LeaveRequestController::class, 'index']);
        Route::post('', [LeaveRequestController::class, 'store']);
    });

    // hr-admin lihat semua pengajuan izin & approve/reject
    Route::prefix('admin/leave-requests')
        ->middleware('permission:leave-management')
        ->group(function () {
            Route::get('', [LeaveRequestController::class, 'adminIndex']);
            Route::patch('{leaveRequest}/status', [LeaveRequestController::class, 'updateStatus']);
        });


    // ========================================================================
    // JOURNALS
    // ========================================================================

    Route::prefix('journals')->group(function () {

        Route::get('', [
            JournalController::class,
            'index'
        ]);

        Route::post('', [
            JournalController::class,
            'store'
        ]);

        Route::get('history', [
            JournalController::class,
            'history'
        ]);

        Route::get('pending-approval', [
            JournalController::class,
            'pendingApproval'
        ]);

        Route::get('approval-history', [
            JournalController::class,
            'approvalHistory'
        ]);

        Route::post('{journal}/approve', [
            JournalController::class,
            'approve'
        ]);

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
    // ADMIN - PERIODE MAGANG (buat countdown & estimasi di dashboard intern)
    // ========================================================================

    Route::prefix('admin/intern-periods')
        ->middleware('role:hr-admin')
        ->group(function () {
            Route::get('', [AdminInternPeriodController::class, 'index']);
            Route::put('{user}', [AdminInternPeriodController::class, 'update']);
        });


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

        Route::get('', [
            AttendanceController::class,
            'index'
        ]);

        Route::get('today', [
            AttendanceController::class,
            'today'
        ]);

        Route::post('check-in', [
            AttendanceController::class,
            'checkIn'
        ]);

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