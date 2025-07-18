<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/images',[ImageController::class,'getImage']);
Route::post('/uploadImageApi',[ImageController::class,'uploadImageApi']);
Route::post('/uploadImageDF',[ImageController::class,'uploadImageDF']);
Route::post('/upload-videos',[VideoController::class,'uploadVideosApi']);
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);
Route::get('/telegram/create-json', [TelegramController::class, 'createImagesJson']);
Route::get('/telegram/update-json', [TelegramController::class, 'updateImagesJson']);
