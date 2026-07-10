<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_genres_index_page(): void
    {
        $user = User::factory()->create();

        Genre::create(([
            'name' => '小説',
        ]));

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertSee('小説');
    }

    public function test_guest_is_redirected_to_login_when_accessing_genres_index_page()
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_genre_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.create'));

        $response->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login_when_accessing_genre_create_page()
    {
        $response = $this -> get(route('genres.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_store_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => 'ミステリー',
        ]);
        
        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => 'ミステリー',
        ]);
    }

    public function test_guest_is_redirected_to_login_when_storing_genre(): void
    {
        $response = $this->post(route('genres.store'), [
            'name' => 'ホラー',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('genres', [
            'name' => 'ホラー',
        ]);
    }

    public function test_genre_store_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_genre_store_requires_name_within_50_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => str_repeat('あ', 51),
        ]);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_genre_store_requires_unique_name(): void
    {
        $user = User::factory()->create();

        Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '小説',
        ]);

        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_authenticated_user_can_view_genre_show_page(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertSee('小説');
    }

    public function test_guest_is_redirected_to_login_when_accessing_genre_show_page(): void
    {
        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->get(route('genres.show', $genre));

        $response->assertRedirect(route('login'));
    }

    public function test_genre_show_page_returns_404_when_genre_does_not_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/genres/999999');

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_view_genre_edit_page(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertStatus(200);
        $response->assertSee('小説');
    }

    public function test_guest_is_redirected_to_login_when_accessing_genre_edit_page(): void
    {
        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->get(route('genres.edit', $genre));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => '文学',
        ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '文学',
        ]);

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_guest_is_redirected_to_login_when_updating_genre(): void
    {
        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->put(route('genres.update', $genre), [
            'name' => '文学',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_update_requires_name(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_update_requires_name_within_50_characters(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => str_repeat('あ', 51),
        ]);

        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_update_requires_unique_name(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        Genre::create([
            'name' => '文学',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => '文学',
        ]);

        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_update_allows_same_name_for_itself(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => '小説',
        ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_authenticated_user_can_delete_genre_without_books(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_user_cannot_delete_genre_with_books(): void
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
            'image_path' => 'https://placehold.co/200x300',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_deleting_genre(): void
    {
        $genre = Genre::create([
            'name' => '小説',
        ]);

        $response = $this->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }
}
