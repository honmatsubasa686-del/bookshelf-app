<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_notifications_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'database',
            'data' => [
                'reading_plan_id' => 1,
                'book_title' => '通知テスト本',
                'due_date' => today()->toDateString(),
                'type' => 'due_today',
                'message' => '読書期限日当日です。',
            ],
        ]);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $response->assertSee('通知一覧');
        $response->assertSee('通知テスト本');
        $response->assertSee('読書期限日当日です。');
        $response->assertSee('既読にする');
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $notification = $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'database',
            'data' => [
                'reading_plan_id' => 1,
                'book_title' => '既読テスト本',
                'due_date' => today()->toDateString(),
                'type' => 'due_today',
                'message' => '読書期限日当日です。',
            ],
        ]);

        $response = $this->post("/notifications/{$notification->id}/read");

        $response->assertRedirect(route('notifications.index'));

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $notification = $owner->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'database',
            'data' => [
                'reading_plan_id' => 1,
                'book_title' => '他ユーザー通知テスト本',
                'due_date' => today()->toDateString(),
                'type' => 'due_today',
                'message' => '読書期限日当日です。',
            ],
        ]);

        $response = $this->post("/notifications/{$notification->id}/read");

        $response->assertStatus(404);

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }
}
