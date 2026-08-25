<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示する。
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $genres = Genre::orderBy('name')->get();

        $books = Book::query()
            ->with(['genres'])
            ->withAvg('reviews', 'rating')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('genre'), function ($query) use ($request) {
                $query->whereHas('genres', function ($query) use ($request) {
                    $query->where('genres.id', $request->genre);
                });
            });

        match ($request->input('sort', 'latest')) {
            'oldest' => $books->oldest(),
            'title' => $books->orderBy('title'),
            'rating' => $books
                ->orderByRaw('reviews_avg_rating IS NULL ASC')
                ->orderByDesc('reviews_avg_rating'),
            default => $books->latest(),
        };

        $books = $books->paginate(10)->withQueryString();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * ISBNを使って書籍情報を検索する。
     */
    public function searchByIsbn(string $isbn): JsonResponse
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁で指定してください。',
            ], 422);
        }

        try {
            $response = Http::timeout(5)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => 'isbn:'.$isbn,
            ]);

            if (! $response->successful()) {
                $message = match ($response->status()) {
                    429 => '外部APIの利用制限に達しました。時間をおいて再度お試しください。',
                    default => '書籍情報の取得に失敗しました。',
                };

                return response()->json([
                    'error' => $message,
                ], 502);
            }

            $items = $response->json('items', []);

            if (empty($items)) {
                return response()->json([
                    'error' => '該当する書籍が見つかりませんでした。',
                ], 404);
            }

            $volumeInfo = $items[0]['volumeInfo'] ?? [];

            return response()->json([
                'title' => $volumeInfo['title'] ?? '',
                'author' => isset($volumeInfo['authors'])
                    ? implode(', ', $volumeInfo['authors'])
                    : '',
                'published_date' => $volumeInfo['publishedDate'] ?? '',
                'description' => $volumeInfo['description'] ?? '',
                'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => '通信エラーが発生しました。',
            ], 500);
        }
    }

    /**
     * 新しい書籍を登録する。
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'published_date' => $validated['published_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->attach($validated['genres']);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): View
    {
        $book->load(['genres', 'user']);

        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book): View
    {
        if ($book->user_id !== auth()->id()) {
            abort(403);
        }

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 指定した書籍を更新する。
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        if ($book->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍情報を更新しました。');
    }

    /**
     *  * 指定した書籍を削除する。
     */
    public function destroy(Book $book): RedirectResponse
    {
        if ($book->user_id !== auth()->id()) {
            abort(403);
        }

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
