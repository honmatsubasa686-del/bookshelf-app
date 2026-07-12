<?php

namespace Tests\Feature;

use App\models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_like_review(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner = User::factory()->create();
        $liker = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => 'いいねされる本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'レビューいいねテスト用の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'いいねされるレビューです。',
        ]);

        $response = $this
            ->actingAs($liker)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $liker->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_authenticated_user_can_unlike_review(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner = User::factory()->create();
        $liker = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => 'いいね解除される本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'レビューいいね解除テスト用の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'いいね解除されるレビューです。',
        ]);

        $liker->likedReviews()->attach($review->id);

        $response = $this
            ->actingAs($liker)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $liker->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_liking_review(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => 'ゲストにいいねされる本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'ゲストいいね操作テスト用の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'ゲストにいいねされるレビューです。'
        ]);

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }

    public function test_review_likes_are_managed_per_user(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner = User::factory()->create();
        $firstLiker = User::factory()->create();
        $secondLiker = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => '複数ユーザーにいいねされる本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '複数ユーザーいいねテスト用の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '複数ユーザーにいいねされるレビューです。',
        ]);

        $firstLiker->likedReviews()->attach($review->id);

        $response = $this
            ->actingAs($secondLiker)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $firstLiker->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $secondLiker->id,
            'review_id' => $review->id,
        ]);
    }
}
