<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewLikeController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;
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
    return redirect()->route('books.index');
});

Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

Route::middleware('auth')->group(function () {

    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->name('books.edit');

    Route::match(['put', 'patch'], '/books/{book}', [BookController::class, 'update'])
        ->name('books.update');

    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('books.destroy');
});

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');

Route::resource('genres', GenreController::class)
    ->middleware('auth');

Route::middleware('auth')->group(function () {

    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
        ->name('reading-plans.index');

    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
        ->name('reading-plans.create');

    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
        ->name('reading-plans.store');

    Route::post('/reading-plans/{readingPlan}/complete', [ReadingPlanController::class, 'complete'])
        ->name('reading-plans.complete');

    Route::get('/reading-plans/{readingPlan}/edit', [ReadingPlanController::class, 'edit'])
        ->name('reading-plans.edit');

    Route::match(['put', 'patch'], '/reading-plans/{readingPlan}', [ReadingPlanController::class, 'update'])
        ->name('reading-plans.update');

    Route::delete('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'destroy'])
        ->name('reading-plans.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');

    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');

    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

Route::get('/favorites', [FavoriteController::class, 'index'])
    ->middleware('auth')
    ->name('favorites.index');

Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');

Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])
    ->middleware('auth')
    ->name('reviews.like');

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');
