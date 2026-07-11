<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_page_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'ランキング表示される本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'ランキング表示テスト用の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '高評価レビューです。'
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSee('評価ランキング TOP 10');
        $response->assertSee('ランキング表示される本');
    }

    public function test_ranking_page_displays_books_ordered_by_average_rating_desc(): void
    {
        $user = User::factory()->create();

        $highRatedBook = Book::create([
            'user_id' => $user->id,
            'title' => '高評価の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '高評価の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $lowRatedBook = Book::create([
            'user_id' => $user->id,
            'title' => '低評価の本',
            'author' => 'テスト著者',
            'isbn' => '9784101010021',
            'published_date' => '2024-01-02',
            'description' => '低評価の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        Review::create([
            'user_id' => $user -> id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
            'comment' => '高評価レビューです。',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
            'comment' => '低評価レビューです。'
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '高評価の本',
            '低評価の本',
        ]);
    }

    public function test_ranking_page_does_not_display_books_without_reviews(): void
    {
        $user = User::factory()->create();

        $reviewedBook = Book::create([
            'user_id' => $user->id,
            'title' => 'レビューありの本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'レビューがある本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $noReviewBook = Book::create([
            'user_id' => $user->id,
            'title' => 'レビューなしの本',
            'author' => 'テスト著者',
            'isbn' => '9784101010021',
            'published_date' => '2024-01-02',
            'description' => 'レビューがない本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $reviewedBook->id,
            'rating' => 4,
            'comment' => 'レビューありです。',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSee('レビューありの本');
        $response->assertDontSee('レビューなしの本');
    }
    public function test_ranking_page_displays_empty_message_when_no_reviews_exist(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'レビューなしの本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'レビューがない本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSee('まだレビューが投稿された書籍がありません。');
        $response->assertDontSee('レビューなしの本');
    }
}
