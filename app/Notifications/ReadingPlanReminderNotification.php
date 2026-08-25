<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private ReadingPlan $readingPlan,
        private string $type
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_title' => $this->readingPlan->book->title,
            'target_date' => $this->readingPlan->target_date->format('Y-m-d'),
            'type' => $this->type,
            'message' => $this->message(),
        ];
    }

    private function message(): string
    {
        return match ($this->type) {
            'before_due' => '読書期限日の3日前です。',
            'due_today' => '読書期限日当日です。',
            'after_due' => '読書期限日から3日経過しています。',
            default => '読書計画のお知らせです。',
        };
    }
}
