<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BookFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_index_page_can_be_displayed(): void
    {
        $response = $this->get('/books');

        $response->assertStatus(200);
    }

    public function test_books_show_page_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から人間社会を描いた作品です。',
            'image_path' => 'https://placechold.co/200x300',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->get('/books/' . $book->id);

        $response->assertStatus(200);
        $response->assertSee('吾輩は猫である');
        $response->assertSee('夏目漱石');
    }

    public function test_books_show_page_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->get('/books/999');

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_view_book_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/books/create');

        $response->assertStatus(200);
        $response->assertSee('書籍登録');
    }

    public function test_guest_is_redirected_to_login_when_accessing_book_create_page(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_store_book(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'description' => '読みやすいコードを書くための考え方を学べる本です。',
            'image_path' => 'https://placehold.co/200x300',
            'genres' => [
                $genre->id,
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'user_id' => $user->id,
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $book = Book::where('isbn', '9784873115658')->first();

        $this->assertTrue($book->genres()->where('genres.id', $genre->id)->exists());
    }

    public function test_book_store_requires_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'genres' => [],
        ]);

        $response->assertSessionHasErrors([
            'title',
            'author',
            'genres',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_store_requires_isbn_to_be_13_digits_when_provided(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '12345', // 12 digits
            'published_date' => '2024-01-01',
            'description' => 'テスト説明です。',
            'image_path' => 'https://placehold.co/200x300',
            'genres' => [
                $genre->id,
            ],
        ]);

        $response->assertSessionHasErrors([
            'isbn'
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_store_requires_unique_isbn(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => '既存の本',
            'author' => '既存著者',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'description' => '既存の説明です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '新しい本',
            'author' => '新しい著者',
            'isbn' => '9784873115658',
            'published_date' => '2024-01-01',
            'description' => '新しい説明です。',
            'image_path' => 'https://placehold.co/200x300',
            'genres' => [
                $genre->id,
            ],
        ]);

        $response->assertSessionHasErrors([
            'isbn'
        ]);

        $this->assertDatabaseCount('books', 1);
    }

    public function test_book_owner_can_view_book_edit_page(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '坊ちゃん',
            'author' => '夏目漱石',
            'isbn' => '9784101010021',
            'published_date' => '1906-04-01',
            'description' => 'まっすぐな主人公を描いた作品です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->get(route('books.edit', $book));

        $response->assertStatus(200);
        $response->assertSee('坊ちゃん');
        $response->assertSee('夏目漱石');
    }

    public function test_user_cannot_view_other_users_book_edit_page(): void
    {
        $ownerUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $ownerUser->id,
            'title' => '他人の本',
            'author' => '他人の著書',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '他人が登録した本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->actingAs($otherUser)->get(route('books.edit', $book));

        $response->assertStatus(403);
    }

    public function test_book_owner_can_update_book(): void
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
            'title' => '変更前のタイトル',
            'author' => '変更前著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '変更前の説明です。',
            'image_path' => 'https://placehold.co/200x300?text=old',
        ]);

        $book->genres()->attach($oldGenre->id);

        $response = $this->actingAs($user)->put(route('books.update', $book), [
            'title' => '変更後タイトル',
            'author' => '変更後著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-02-01',
            'description' => '変更後の説明です。',
            'image_path' => 'https://placehold.co/200x300?text=new',
            'genres' => [
                $newGenre->id,
            ],
        ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '変更後タイトル',
            'author' => '変更後著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-02-01',
            'description' => '変更後の説明です。',
            'image_path' => 'https://placehold.co/200x300?text=new',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);
    }

    public function test_user_cannot_update_other_users_book(): void
    {
        $ownerUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $book = Book::create([
            'user_id' => $ownerUser->id,
            'title' => '他人の本',
            'author' => '他人の著書',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '他人が登録した本です。',
            'image_path' => 'https://placehold.co/200x300?text=old',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($otherUser)->put(route('books.update', $book), [
            'title' => '勝手に更新',
            'author' => '勝手な著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-02-01',
            'description' => '勝手に変更した説明です。',
            'image_path' => 'https://placehold.co/200x300?text=new',
            'genres' => [
            $genre->id,
            ],
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '他人の本',
            'author' => '他人の著書',
            'description' => '他人が登録した本です。',
            'image_path' => 'https://placehold.co/200x300?text=old',
        ]);
    }

    public function test_book_owner_can_delete_book(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '削除する本',
            'author' => '削除する著者',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '削除対象の本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_book(): void
    {
        $ownerUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::create([
            'user_id' => $ownerUser->id,
            'title' => '他人の本',
            'author' => '他人の著書',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '他人が登録した本です。',
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $response = $this->actingAs($otherUser)->delete(route('books.destroy', $book));

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '他人の本',
        ]);
    }
}

