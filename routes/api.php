<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\VariantController;       
use App\Http\Controllers\VariantOptionController; 
use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\WebhookController; 
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\MemberController; 
use App\Http\Controllers\Api\StockController;   
use App\Http\Controllers\Api\PointSettingController; 
use App\Http\Controllers\Api\MemberAuthController;
use App\Http\Controllers\Api\MemberProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/member/register', [MemberAuthController::class, 'register']);
Route::post('/member/login', [MemberAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Route ini sekarang aman dan hanya bisa diakses oleh member dengan token valid
    Route::get('/member/profile', [MemberProfileController::class, 'show']);
    // Jika nanti ada fitur lain untuk member (ganti password, dll), letakkan di sini.
    Route::get('/member/points-history', [MemberProfileController::class, 'getPointHistory']);

    Route::get('/transactions/{invoice_number}', [TransactionController::class, 'show']);

    Route::post('/member/profile/update', [MemberProfileController::class, 'update']);
    Route::post('/member/profile/change-password', [MemberProfileController::class, 'changePassword']);
});

// Authentication Route
Route::middleware(['auth', 'json'])->prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->withoutMiddleware('auth');
    Route::delete('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
});

Route::prefix('setting')->group(function () {
    Route::get('', [SettingController::class, 'index']);
});

Route::middleware(['auth', 'verified'])->prefix('master')->group(function() {
    Route::apiResource('products', ProductController::class);
    // Jika ada rute lain yang akan upload file, letakkan juga di sini.
});

Route::middleware(['auth', 'verified' , 'json'])->group(function () {
    Route::post('/profile', [UserController::class, 'updateProfile']);

    // PENAMBAHAN: Route untuk mengubah email
    Route::post('/profile/change-email', [UserController::class, 'changeEmail']);

    // PENAMBAHAN: Route untuk mengubah password
    Route::post('/profile/change-password', [UserController::class, 'changePassword']);

    Route::prefix('setting')->middleware('can:setting')->group(function () {
        Route::post('', [SettingController::class, 'update']);
    });

    Route::prefix('master')->group(function () {
        Route::apiResource('users', UserController::class);

        // --- Role Routes ---
        Route::middleware('can:master-role')->group(function () {
            Route::get('roles', [RoleController::class, 'get'])->withoutMiddleware('can:master-role');
            Route::post('roles', [RoleController::class, 'index']);
            Route::post('roles/store', [RoleController::class, 'store']);
            Route::apiResource('roles', RoleController::class)
                ->except(['index', 'store']);
        });

        // --- Other Master Routes ---
        // Route::apiResource('products', ProductController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('variants', VariantController::class);
        Route::apiResource('promos', PromoController::class);
        Route::post('variants/{variant}/options', [VariantOptionController::class, 'store']);
        Route::delete('variant-options/{option}', [VariantOptionController::class, 'destroy']);
        Route::get('/variants-for-cashier', [ProductController::class, 'getVariantsForCashier']);

        Route::post('stock/adjust', [StockController::class, 'adjustStock']);
        Route::get('variants/{variant}/stock-history', [StockController::class, 'history']); 

        Route::get('stock/history', [StockController::class, 'fullHistory']);
        Route::get('stock/history/export', [StockController::class, 'exportHistory']);
        Route::get('stock-overview', [ProductController::class, 'stockOverview']);    

        Route::prefix('points')->group(function() {
            // Jika perlu, tambahkan middleware 'can' untuk permission
            // ->middleware('can:master-point-settings') 
            Route::get('/settings', [PointSettingController::class, 'index']);
            Route::post('/settings', [PointSettingController::class, 'store']);
        });

    });
    
    // --- Other Main Routes ---
    Route::post('/checkout', [TransactionController::class, 'store']);
    Route::get('/transactions/history', [TransactionController::class, 'getHistory']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);

    Route::post('/transactions/{invoice_number}/cancel', [TransactionController::class, 'cancel']);

    Route::get('/dashboard-stats', [DashboardController::class, 'getStats']);
    Route::get('/reports', [DashboardController::class, 'getReports']);
    Route::post('/payment/create', [MidtransController::class, 'createTransaction']);
    Route::post('/promos/validate', [PromoController::class, 'validatePromo']);

    Route::get('/products/barcode/{barcode}', [ProductController::class, 'findByBarcode']);
    Route::get('/members/search', [MemberController::class, 'search']);
    Route::apiResource('/master/members', MemberController::class);

});

    Route::post('/midtrans/webhook', [WebhookController::class, 'handler']);