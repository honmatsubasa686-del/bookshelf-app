<?php

namespace Tests\Feature;

use App\Models\Book;
use \App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_review(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の観点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->actingAs($reviewer)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'とても面白い作品でした。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い作品でした。',
        ]);
    }

    public function test_guest_is_redirected_to_login_when_storing_review(): void
    {
        $owner = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'ゲスト投稿テストです。',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_store_requires_rating(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->actingAs($reviewer)->post(route('reviews.store', $book), [
            'rating' => '',
            'comment' => '評価なしレビューです。',
        ]);

        $response->assertSessionHasErrors([
            'rating',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_store_requires_rating_between_1_and_5(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->actingAs($reviewer)->post(route('reviews.store', $book), [
            'rating' => 6,
            'comment' => '範囲外評価レビューです。',
        ]);

        $response->assertSessionHasErrors([
            'rating',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_book_show_page_displays_reviews(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create([
            'name' => 'レビュー太郎',
        ]);

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い作品でした。',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('レビュー太郎');
        $response->assertSee('とても面白い作品でした。');
    }

    public function test_review_owner_can_view_review_edit_page(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '編集前のコメントです。',
        ]);

        $response = $this->actingAs($reviewOwner)->get(route('reviews.edit', $review));

        $response->assertStatus(200);
        $response->assertSee('編集前のコメントです。');
    }

    public function test_user_cannot_view_other_users_review_edit_page(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '他人のレビューです。',
        ]);

        $response = $this->actingAs($otherUser)->get(route('reviews.edit', $review));

        $response->assertStatus(403);
    }

    public function test_review_owner_can_update_review(): void
    {
        $bookOwner = user::factory()->create();
        $reviewOwner = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '編集前のコメントです。',
        ]);

        $response = $this->actingAs($reviewOwner)->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '編集後のコメントです。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '編集後のコメントです。',
        ]);
    }

    public function test_user_cannot_update_other_users_review(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '元のコメントです。',
        ]);

        $response = $this->actingAs($otherUser)->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '他人が書き換えようとしたコメントです。',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '元のコメントです。',
        ]);
    }

    public function test_review_owner_can_delete_review(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除されるコメントです。',
        ]);

        $response = $this->actingAs($reviewOwner)->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_review(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '消されてはいけないコメントです。',
        ]);

        $response = $this->actingAs($otherUser)->delete(route('reviews.destroy', $review));

        $response->assertStatus(403);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '消されてはいけないコメントです。',
        ]);
    }
}
