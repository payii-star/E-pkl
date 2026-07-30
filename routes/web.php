<?php

use AmrShawky\LaravelCurrency\Facade\Currency;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberPageController; 

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::prefix('member')->name('member.')->group(function () {
    // Rute ini nantinya untuk menampilkan kartu member setelah login
    // Untuk sekarang, kita buat agar bisa diakses langsung dengan ID untuk tes
    Route::get('/card/{member}', [MemberPageController::class, 'showCard'])->name('card');
});

// Route::get('/', function () {
//     return view('app');
// });

Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api\/)[\/\w\.-]*');
