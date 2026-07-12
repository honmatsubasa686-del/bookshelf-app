<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteFeatureTest extends TestCase
{
    use RefreshDatabase;


    public function test_authenticated_user_can_view_favorites_index_page(): void
    {
        $user = User::factory()->create();

             $book = Book::create([
            'user_id' => $user->id,
            'title' => 'お気に入りの本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'お気に入り一覧に表示される本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee('お気に入りの本');
    }

    public function test_guest_is_redirected_to_login_when_accessing_favorites_index_page(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_favorites_index_displays_only_authenticated_users_favorite_books(): void
    {
        $user = User::factory()->create();
        $otherUser = user::factory()->create();

        $myFavoriteBook = Book::create([
            'user_id' => $user->id,
            'title' => '自分のお気に入り本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '自分のお気に入りです。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $otherFavoriteBook = Book::create([
            'user_id' => $otherUser->id,
            'title' => '他人のお気に入り本',
            'author' => '別の著者',
            'isbn' => '9784101010021',
            'published_date' => '2024-01-02',
            'description' => '他人のお気に入りです。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $user->favoriteBooks()->attach($myFavoriteBook->id);
        $otherUser->favoriteBooks()->attach($otherFavoriteBook->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee('自分のお気に入り本');
        $response->assertDontSee('他人のお気に入り本');
    }

    public function test_authenticated_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'お気に入り追加する本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'お気に入り追加テストです。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_authenticated_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'お気に入り解除する本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'お気に入り解除テストです。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $user->favoriteBooks()->attach($book->id);

        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_toggling_favorite(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'ゲスト操作される本',
            'author' => 'テスト著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => 'ゲストお気に入り操作テストです。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);
    }
}
