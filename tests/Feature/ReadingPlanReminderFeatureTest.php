<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanReminderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_before_due_reminder_notification(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '期限3日前通知テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010045',
            'published_date' => '2024-01-01',
            'description' => '期限3日前通知テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $readingPlan->refresh();

        $this->assertNotNull($readingPlan->reminder_before_sent_at);
        $this->assertNull($readingPlan->reminder_due_sent_at);

        $notification = $user->notifications()->first();

        $this->assertSame('before_due', $notification->data['type']);
        $this->assertSame($readingPlan->id, $notification->data['reading_plan_id']);
        $this->assertSame('期限3日前通知テスト本', $notification->data['book_title']);
        $this->assertSame(today()->addDays(3)->toDateString(), $notification->data['target_date']);
        $this->assertSame('読書期限日の3日前です。', $notification->data['message']);
    }

    public function test_command_creates_due_today_reminder_notification(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '期限当日通知テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010052',
            'published_date' => '2024-01-01',
            'description' => '期限当日通知テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $readingPlan->refresh();

        $this->assertNull($readingPlan->reminder_before_sent_at);
        $this->assertNotNull($readingPlan->reminder_due_sent_at);

        $notification = $user->notifications()->first();

        $this->assertSame('due_today', $notification->data['type']);
        $this->assertSame($readingPlan->id, $notification->data['reading_plan_id']);
        $this->assertSame('期限当日通知テスト本', $notification->data['book_title']);
        $this->assertSame(today()->toDateString(), $notification->data['target_date']);
        $this->assertSame('読書期限日当日です。', $notification->data['message']);
    }

    public function test_command_creates_after_due_reminder_notification(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '期限3日後通知テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010045',
            'published_date' => '2024-01-01',
            'description' => '期限3日後通知テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Expired->value,
        ]);

        $readingPlan->refresh();

        $this->assertNotNull($readingPlan->reminder_after_sent_at);
        $this->assertNull($readingPlan->reminder_due_sent_at);

        $notification = $user->notifications()->first();

        $this->assertSame('after_due', $notification->data['type']);
        $this->assertSame($readingPlan->id, $notification->data['reading_plan_id']);
        $this->assertSame('期限3日後通知テスト本', $notification->data['book_title']);
        $this->assertSame(today()->subDays(3)->toDateString(), $notification->data['target_date']);
        $this->assertSame('読書期限日から3日経過しています。', $notification->data['message']);
    }

    public function test_command_does_not_create_duplicate_before_due_reminder_notification(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '3日前通知重複防止テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010069',
            'published_date' => '2024-01-01',
            'description' => '3日前通知重複防止テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
            'reminder_before_sent_at' => now(),
        ]);

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 0);

        $readingPlan->refresh();

        $this->assertNotNull($readingPlan->reminder_before_sent_at);
        $this->assertNull($readingPlan->reminder_due_sent_at);
    }

    public function test_command_does_not_create_duplicate_due_today_reminder_notification(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '当日通知重複防止テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010090',
            'published_date' => '2024-01-01',
            'description' => '当日通知重複防止テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
            'reminder_due_sent_at' => now(),
        ]);
        $this->assertNotNull($readingPlan->reminder_due_sent_at);
    }

    public function test_command_does_not_create_duplicate_after_due_reminder_notification(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '3日後通知重複防止テスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010076',
            'published_date' => '2024-01-01',
            'description' => '3日後通知重複防止テスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Expired,
            'reminder_after_sent_at' => now(),
        ]);

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 0);

        $readingPlan->refresh();

        $this->assertNull($readingPlan->reminder_before_sent_at);
        $this->assertNotNull($readingPlan->reminder_after_sent_at);
    }

    public function test_command_expires_overdue_reading_plan(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '期限切れのテスト本',
            'author' => 'テスト太郎',
            'isbn' => '9784101010083',
            'published_date' => '2024-01-01',
            'description' => '期限切れテスト用の本です。',
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Expired->value,
        ]);

        $readingPlan->refresh();

        $this->assertSame(ReadingPlanStatus::Expired, $readingPlan->status);
        $this->assertNotNull($readingPlan->expired_at);
        $this->assertDatabaseCount('notifications', 0);
    }
}
