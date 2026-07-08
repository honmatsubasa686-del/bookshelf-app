<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController as ApiBookController;

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
    Route::get('/books', [ApiBookController::class, 'index']);
    Route::post('/books', [ApiBookController::class, 'store']);
    Route::get('/books/{book}', [ApiBookController::class, 'show']);
    Route::put('/books/{book}', [ApiBookController::class, 'update']);
    Route::delete('/books/{book}', [ApiBookController::class, 'destroy']);
});
