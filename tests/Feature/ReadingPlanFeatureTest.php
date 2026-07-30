<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_reading_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '読書計画テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010014',
            'published_date' => '2024-01-01',
            'description' => '読書計画テスト用の本です。',
        ]);

        $response = $this->post('/reading-plans', [
            'book_id' => $book->id,
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::Planned->value,
        ]);

        $readingPlan = ReadingPlan::first();

        $this->assertSame($user->id, $readingPlan->user_id);
        $this->assertSame($book->id, $readingPlan->book_id);
        $this->assertSame(ReadingPlanStatus::Planned, $readingPlan->status);
    }

    public function test_user_can_complete_own_reading_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '読了テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010021',
            'published_date' => '2024-01-01',
            'description' => '読了テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::Planned,
        ]);

        $response = $this->post("/reading-plans/{$readingPlan->id}/complete");

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        $readingPlan->refresh();

        $this->assertSame(ReadingPlanStatus::Completed, $readingPlan->status);
    }

    public function test_user_cannot_complete_other_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '他ユーザー読了テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010038',
            'published_date' => '2024-01-01',
            'description' => '他ユーザー読了テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::Planned,
        ]);

        $response = $this->post("/reading-plans/{$readingPlan->id}/complete");

        $response->assertStatus(403);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::Planned->value,
        ]);
    }
}
