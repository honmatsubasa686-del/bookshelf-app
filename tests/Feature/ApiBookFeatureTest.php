<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\review;
use App\Models\Book;
use App\Models\user;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBookFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_books_index_returns_books(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'API一覧の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'API一覧テスト用の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'description',
                    'image_path',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ]
        ]);

        $response->assertJsonPath('data.0.title', 'API一覧の本');
        $response->assertJsonPath('meta.per_page', 12);
        $response->assertJsonPath('meta.total', 1);
    }

    public function test_api_books_index_can_filter_by_title(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'Laravelの本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => 'PHP入門',
            'author' => 'テスト著者',
            'isbn' => '9784101010021',
            'published_date' => '2024-01-02',
            'description' => 'PHPの本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->getJson('/api/v1/books?title=Laravel');

        $response->assertStatus(200);

        $response->assertJsonPath('data.0.title', 'Laravel入門');
        $response->assertJsonPath('meta.total', 1);

        $response->assertJsonMissing([
            'title' => 'PHP入門',
        ]);
    }

    public function test_api_books_show_returns_book_detail(): void
    {
        $user = User::factory()->create([
            'name' => 'レビュー太郎',
        ]);

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'API詳細の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'API詳細テスト用の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $book->genres()->attach($genre->id);

        review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'API詳細に表示されるレビューです。',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);

        $response->assertJsonPath('data.title', 'API詳細の本');
        $response->assertJsonPath('data.author', 'テスト著者');
        $response->assertJsonPath('data.genres.0.name', '技術書');
        $response->assertJsonPath('data.reviews.0.user_name', 'レビュー太郎');
        $response->assertJsonPath('data.reviews.0.rating', 5);
        $response->assertJsonPath('data.reviews.0.comment', 'API詳細に表示されるレビューです。');
    }

    public function test_api_books_show_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertStatus(404);
    }

    public function test_api_books_store_creates_book(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書'
        ]);

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $user->id,
            'title' => 'API登録の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'API登録テスト用の本です。',
            'genres' => [
                $genre->id,
            ],
        ]);

        $response->assertStatus(201);

        $response->assertJsonPath('data.title', 'API登録の本');
        $response->assertJsonPath('data.author', 'テスト著者');
        $response->assertJsonPath('data.genres.0.name', '技術書');

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'API登録の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
        ]);

        $book = Book::where('title', 'API登録の本')->first();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_api_books_store_required_fields(): void
    {
        $response = $this->postJson('/api/v1/books', [
            'user_id' => '',
            'title' => '',
            'author' => '',
            'published_date' => '',
            'genres' => [],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'user_id',
            'title',
            'author',
            'published_date',
            'genres',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_api_books_store_requires_isbn_to_be_13_digits_when_provided(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術者',
        ]);

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $user->id,
            'title' => 'ISBN不正の本',
            'author' => 'テスト著者',
            'isbn' => '123456789012',
            'published_date' => '2024-01-01',
            'description' => 'ISBN不正テストです。',
            'genres' => [
                $genre->id,
            ],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'isbn',
        ]);

        $this->assertDatabaseMissing('books', [
            'title' => 'ISBN不正の本',
        ]);
    }

    public function test_api_books_store_requires_unique_isbn(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術者',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => '既存の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '既存本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $user->id,
            'title' => 'ISBN重複の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-02',
            'description' => 'ISBN重複テストです。',
            'genres' => [
                $genre->id,
            ],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'isbn',
        ]);

        $this->assertDatabaseMissing('books', [
            'title' => 'ISBN重複の本',
        ]);
    }

    public function test_api_books_store_requires_existing_genres(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $user->id,
            'title' => '存在しないジャンルの本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '存在しないジャンルテストです。',
            'genres' => [
                999999,
            ],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'genres.0',
        ]);

        $this->assertDatabaseMissing('books', [
            'title' => '存在しないジャンルの本',
        ]);
    }

    public function test_api_books_update_updates_book(): void
    {
        $user = User::factory()->create();

        $oldGenre = Genre::create([
            'name' => '小説',
        ]);

        $newGenre = Genre::create([
            'name' => '技術書',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '更新前の本',
            'author' => '更新前の著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '更新前の説明です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $book->genres()->attach($oldGenre->id);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $user->id,
            'title' => '更新後の本',
            'author' => '更新後の著者',
            'isbn' => '9784101010021',
            'published_date' => '2024-02-01',
            'description' => '更新後の説明です。',
            'genres' => [
                $newGenre->id,
            ],
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.title', '更新後の本');
        $response->assertJsonPath('data.author', '更新後の著者');
        $response->assertJsonPath('data.isbn', '9784101010021');
        $response->assertJsonPath('data.genres.0.name', '技術書');

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);
    }

    public function test_api_books_update_requires_required_fields(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '更新対象の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '更新対象です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => '',
            'title' => '',
            'author' => '',
            'published_date' => '',
            'genres' => [],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'user_id',
            'title',
            'author',
            'published_date',
            'genres',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新対象の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
        ]);
    }

    public function test_api_books_update_requires_unique_isbn_except_itself(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '更新対象の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '更新対象です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => '別の本',
            'author' => '別の著者',
            'isbn' => '9784101010021',
            'published_date' => '2024-01-02',
            'description' => '別の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $user->id,
            'title' => '更新しようとした本',
            'author' => 'テスト著者',
            'isbn' => '9784101010021',
            'published_date' => '2024-02-01',
            'description' => 'ISBN重複更新テストです。',
            'genres' => [
                $genre->id,
            ],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'isbn',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新対象の本',
            'isbn' => '9784101010014',
        ]);
    }

    public function test_api_books_update_returns_404_when_book_does_not_exist(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $response = $this->putJson('/api/v1/books/999999', [
            'user_id' => $user->id,
            'title' => '存在しない本の更新',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '存在しない本の更新テストです。',
            'genres' => [
                $genre->id,
            ],
        ]);

        $response->assertStatus(404);

        $this->assertDatabaseMissing('books', [
            'title' => '存在しない本の更新',
        ]);
    }

    public function test_api_books_destroy_deletes_book(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '削除対象の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '削除対象です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '削除対象の本',
        ]);
    }

    public function test_api_books_destroy_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->deleteJson('/api/v1/books/999999');

        $response->assertStatus(404);
    }
}
