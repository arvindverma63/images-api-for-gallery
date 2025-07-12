<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

// Show login form
Route::get('/login', [AuthController::class, 'login'])->name('login');

// Handle login form submission
Route::post('/login', [AuthController::class, 'doLogin'])->name('do.login');

// Show gallery page (only for authenticated session)
Route::get('/gallery', [AuthController::class, 'gallery'])->name('gallery');

// Route that checks session and redirects accordingly (uses middleware)
Route::get('/check-password', function () {
    // This route just triggers the session check
})->middleware('password.check');

// Optional logout route to clear session
Route::post('/logout', function () {
    session()->forget('password');
    return redirect('/login');
})->name('logout');

Route::get('/gallery', [GalleryController::class, 'index'])
    ->middleware('password.check')
    ->name('gallery.index');

Route::get('/videos',[VideoController::class,'index'])->name('videos.index');
