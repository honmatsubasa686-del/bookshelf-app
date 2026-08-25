<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function index(): View
    {
        $rankedBooks = Book::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->with('genres')
            ->having('reviews_count', '>', 0)
            ->orderByDesc('reviews_avg_rating')
            ->paginate(10);

        return view('ranking.index', compact('rankedBooks'));
    }
}
