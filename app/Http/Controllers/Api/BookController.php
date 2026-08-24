<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(IndexBookRequest $request)
    {
        $query = Book::query()
            ->with(['genres', 'reviews.user']);

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('genre_id')) {
            $genreId = $request->input('genre_id');
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        $perPage = (int) $request->input('per_page', 20);

        $books = $query->latest()->paginate($perPage);

        return BookResource::collection($books);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book = Book::create([
            ...$validated,
            'user_id' => $request->user()->id,
            ]);

        $book->genres()->attach($genres);

        $book->load(['genres', 'reviews.user']);

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load(['genres', 'reviews.user']);

        return new BookResource($book);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genres);

        $book->load(['genres', 'reviews.user']);

        return new BookResource($book);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
