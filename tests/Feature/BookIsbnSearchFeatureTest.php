<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookIsbnSearchFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_isbn_search_returns_422_when_isbn_is_not_13_digits(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake();

        $response = $this->getJson('/books/isbn/123456789012');

        $response->assertStatus(422);

        $response->assertJson([
            'error' => 'ISBNは13桁で指定してください。',
        ]);

        Http::assertNothingSent();
    }

    public function test_isbn_search_returns_book_information_when_google_books_api_succeeds(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake(function () {
            return Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravelテスト入門',
                            'authors' => [
                                'テスト太郎',
                                'テスト花子',
                            ],
                            'publishedDate' => '2024-01-01',
                            'description' => 'Laravelのテストについて学ぶ本です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/thumbnail.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200);
        });

        $response = $this->getJson('/books/isbn/9784101010014');

        $response->assertStatus(200);

        $response->assertJson([
            'title' => 'Laravelテスト入門',
            'author' => 'テスト太郎, テスト花子',
            'published_date' => '2024-01-01',
            'description' => 'Laravelのテストについて学ぶ本です。',
            'image_url' => 'https://example.com/thumbnail.jpg',
        ]);

        Http::assertSentCount(1);
    }

    public function test_isbn_search_returns_502_when_google_books_api_rate_limited(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake(function () {
            return Http::response([], 429);
        });

        $response = $this->getJson('/books/isbn/9784101010014');

        $response->assertStatus(502);

        $response->assertJson([
            'error' => '外部APIの利用制限に達しました。時間をおいて再度お試しください。',
        ]);

        Http::assertSentCount(1);
    }

    public function test_isbn_search_returns_500_when_google_books_api_connection_fails(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake(function () {
            throw new \Exception('Connection failed');
        });

        $response = $this->getJson('/books/isbn/9784101010014');

        $response->assertStatus(500);

        $response->assertJson([
            'error' => '通信エラーが発生しました。',
        ]);
    }

    public function test_isbn_search_returns_404_when_book_is_not_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake(function () {
            return Http::response([
                'items' => [],
            ], 200);
        });

        $response = $this->getJson('/books/isbn/9784101010014');

        $response->assertStatus(404);

        $response->assertJson([
            'error' => '該当する書籍が見つかりませんでした。',
        ]);

        Http::assertSentCount(1);
    }
}
