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
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $readingPlan = ReadingPlan::first();

        $this->assertSame($user->id, $readingPlan->user_id);
        $this->assertSame($book->id, $readingPlan->book_id);
        $this->assertSame(ReadingPlanStatus::InProgress, $readingPlan->status);
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
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
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
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->post("/reading-plans/{$readingPlan->id}/complete");

        $response->assertStatus(403);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_user_can_view_edit_page_for_own_reading_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '編集画面テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010137',
            'published_date' => '2024-01-01',
            'description' => '編集画面テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->get("/reading-plans/{$readingPlan->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('読書計画編集');
        $response->assertSee('編集画面テスト本');
        $response->assertSee('更新');
    }

    public function test_user_can_update_own_reading_plan_target_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '期限日更新テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010144',
            'published_date' => '2024-01-01',
            'description' => '期限日更新テストの本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $newTargetDate = now()->addWeeks(2)->toDateString();

        $response = $this->put("/reading-plans/{$readingPlan->id}", [
            'target_date' => $newTargetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $newTargetDate,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $readingPlan->refresh();

        $this->assertSame($newTargetDate, $readingPlan->target_date->toDateString());
    }

    public function test_user_cannot_update_reading_plan_target_date_to_past_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '過去日限定テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010151',
            'published_date' => '2024-01-01',
            'description' => '過去日期限テスト用の本です。',
        ]);

        $originalDueDate = now()->addWeek()->toDateString();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $originalDueDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->from("/reading-plans/{$readingPlan->id}/edit")
            ->put("/reading-plans/{$readingPlan->id}", [
                'target_date' => now()->subDay()->toDateString(),
            ]);

        $response->assertRedirect("/reading-plans/{$readingPlan->id}/edit");
        $response->assertSessionHasErrors('target_date');

        $readingPlan->refresh();

        $this->assertSame($originalDueDate, $readingPlan->target_date->toDateString());
    }

    public function test_user_cannot_edit_completed_reading_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '読了済み編集不可テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010168',
            'published_date' => '2024-01-01',
            'description' => '読了済み編集不可テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->get("/reading-plans/{$readingPlan->id}/edit");

        $response->assertStatus(403);
    }

    public function test_user_cannot_update_expired_reading_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '期限切れ更新不可テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010175',
            'published_date' => '2024-01-01',
            'description' => '期限切れ更新不可テスト用の本です。',
        ]);

        $originalTargetDate = now()->addWeek()->toDateString();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $originalTargetDate,
            'status' => ReadingPlanStatus::Expired,
        ]);

        $response = $this->put("/reading-plans/{$readingPlan->id}", [
            'target_date' => now()->addWeeks(2)->toDateString(),
        ]);

        $response->assertStatus(403);

        $readingPlan->refresh();

        $this->assertSame($originalTargetDate, $readingPlan->target_date->toDateString());
        $this->assertSame(ReadingPlanStatus::Expired, $readingPlan->status);
    }

    public function test_user_can_delete_own_reading_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '削除テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010182',
            'published_date' => '2024-01-01',
            'description' => '削除テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->delete("/reading-plans/{$readingPlan->id}");

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }
}
