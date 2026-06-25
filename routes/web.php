<?php

use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BookController;
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

Route::resource('books', BookController::class);

Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

//仮ルート
Route::get('/ranking', function () {
    return 'ranking index';
})->name('ranking.index');

Route::get('/favorites', function () {
    return 'favorites index';
})->name('favorites.index');

Route::get('/genres', function () {
    return 'genres index';
})->name('genres.index');
