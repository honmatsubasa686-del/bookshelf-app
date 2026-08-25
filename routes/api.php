<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\BookController as ApiBookController;
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

Route::prefix('v1')->group(function () {
    Route::post('/tokens', [AuthTokenController::class, 'store']);

    Route::get('/books', [ApiBookController::class, 'index']);
    Route::get('/books/{book}', [ApiBookController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/books', [ApiBookController::class, 'store']);
        Route::put('/books/{book}', [ApiBookController::class, 'update']);
        Route::delete('/books/{book}', [ApiBookController::class, 'destroy']);

    });
});
