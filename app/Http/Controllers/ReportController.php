<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $reviews = Review::with('book.genres')
            ->where('user_id', auth()->id())
            ->get();

        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $reviews->pluck('book_id')->unique()->count(),
                'average_rating' => $reviews->avg('rating') ?? 0,
            ],
            'rating_distribution' => collect(range(1, 5))
                ->map(fn($rating) => $reviews->where('rating', $rating)->count()),
            'top_rated_books' => $reviews
                ->where('rating', '>=', 4)
                ->sortByDesc('rating')
                ->take(5)
                ->map(fn($review) => [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,
                ])
                ->values()
                ->all(),
            'genre_ratings' => $this->buildGenreRatings($reviews),
        ];

        return view('reports.index', compact('stats'));
    }

    private function buildGenreRatings($reviews): array
    {
        return $reviews
            ->flatMap(function ($review) {
                return $review->book->genres->map(fn($genre) => [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'rating' => $review->rating,
                ]);
            })
            ->groupBy('id')
            ->map(function ($items) {
                return [
                    'id' => $items->first()['id'],
                    'name' => $items->first()['name'],
                    'count' => $items->count(),
                    'average_rating' => $items->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values()
            ->all();
    }
}
