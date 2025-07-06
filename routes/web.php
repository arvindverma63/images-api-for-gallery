<?php

use App\Http\Controllers\AuthController;
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

Route::get('/login', [AuthController::class, 'login']);
Route::post('/login', [AuthController::class, 'doLogin']);
Route::get('/check-password', function () {
    // This route is just to trigger the middleware

    Route::get('/gallery', [AuthController::class, 'gallery']);
})->middleware('password.check');
