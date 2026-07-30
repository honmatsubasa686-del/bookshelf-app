<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_reading_report(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $firstBook = Book::create([
            'user_id' => $user->id,
            'title' => '高評価テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010090',
            'published_date' => '2024-01-01',
            'description' => 'レポートテスト用の本です。',
        ]);

        $secondBook = Book::create([
            'user_id' => $user->id,
            'title' => '読了テスト本',
            'author' => 'テスト花子',
            'isbn' => '9784101010106',
            'published_date' => '2024-01-01',
            'description' => 'レポートテスト用の本です。',
        ]);

        $firstBook->genres()->attach($genre->id);
        $secondBook->genres()->attach($genre->id);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $firstBook->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $secondBook->id,
            'rating' => 4,
            'comment' => '良い本でした。',
        ]);

        $response = $this->get('/reports');

        $response->assertStatus(200);
        $response->assertSee('マイ読書レポート');

        $response->assertViewHas('stats', function ($stats) use ($firstBook, $secondBook, $genre) {
            return $stats['summary']['total_reviews'] === 2
                && $stats['summary']['books_read'] === 2
                && (float) $stats['summary']['average_rating'] === 4.5
                && $stats['rating_distribution']->get(3) === 1
                && $stats['rating_distribution']->get(4) === 1
                && count($stats['top_rated_books']) === 2
                && $stats['top_rated_books'][0]['id'] === $firstBook->id
                && $stats['top_rated_books'][0]['title'] === '高評価テスト本'
                && $stats['top_rated_books'][1]['id'] === $secondBook->id
                && count($stats['genre_ratings']) === 1
                && $stats['genre_ratings'][0]['id'] === $genre->id
                && $stats['genre_ratings'][0]['name'] === 'テストジャンル'
                && $stats['genre_ratings'][0]['count'] === 2
                && (float) $stats['genre_ratings'][0]['average_rating'] === 4.5;
        });
    }

    public function test_report_does_not_include_other_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user);

        $ownBook = Book::create([
            'user_id' => $user->id,
            'title' => '本人レビュー本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010113',
            'published_date' => '2024-01-01',
            'description' => '本人レビュー用の本です。',
        ]);

        $otherBook = Book::create([
            'user_id' => $otherUser->id,
            'title' => '他ユーザーレビュー本',
            'author' => 'テスト花子',
            'isbn' => '9784101010120',
            'published_date' => '2024-01-01',
            'description' => '他ユーザーレビュー用の本です。',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $ownBook->id,
            'rating' => 5,
            'comment' => '本人のレビューです。',
        ]);

        Review::create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'rating' => 1,
            'comment' => '他ユーザーのレビューです。',
        ]);

        $response = $this->get('/reports');

        $response->assertStatus(200);

        $response->assertViewHas('stats');

        $stats = $response->viewData('stats');

        $this->assertSame(1, $stats['summary']['total_reviews']);
        $this->assertSame(1, $stats['summary']['books_read']);
        $this->assertSame(5.0, (float) $stats['summary']['average_rating']);

        $this->assertCount(1, $stats['top_rated_books']);
        $this->assertSame($ownBook->id, $stats['top_rated_books'][0]['id']);
        $this->assertSame('本人レビュー本', $stats['top_rated_books'][0]['title']);

        $response->assertSee('本人レビュー本');
        $response->assertDontSee('他ユーザーレビュー本');
    }
}
