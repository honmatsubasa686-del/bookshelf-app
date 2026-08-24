<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;

class ProcessReadingPlansCommand extends Command
{

    protected $signature = 'reading-plans:process';

    protected $description = 'Process expired reading plans and send reminder notifications';

    public function handle(): int
    {
        $this->expireOverduePlans();
        $this->sendBeforeDueReminders();
        $this->sendDueTodayReminders();
        $this->sendAfterDueReminders();

        $this->info('Reading plans processed successfully.');

        return self::SUCCESS;
    }

    private function expireOverduePlans(): void
    {
        ReadingPlan::where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Expired,
                'expired_at' => now(),
            ]);
    }

    private function sendBeforeDueReminders(): void
    {
        ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', today()->addDays(3))
            ->whereNull('reminder_before_sent_at')
            ->get()
            ->each(function (ReadingPlan $readingPlan) {
                $readingPlan->user->notify(
                    new ReadingPlanReminderNotification($readingPlan, 'before_due')
                );

                $readingPlan->update([
                    'reminder_before_sent_at' => now(),
                ]);
            });
    }

    private function sendAfterDueReminders(): void
    {
        ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Expired)
            ->whereDate('target_date', today()->subDays(3))
            ->whereNull('reminder_after_sent_at')
            ->get()
            ->each(function (ReadingPlan $readingPlan) {
                $readingPlan->user->notify(
                    new ReadingPlanReminderNotification($readingPlan, 'after_due')
                );

                $readingPlan->update([
                    'reminder_after_sent_at' => now(),
                ]);
            });
    }

    private function sendDueTodayReminders(): void
    {
        ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', today())
            ->whereNull('reminder_due_sent_at')
            ->get()
            ->each(function (ReadingPlan $readingPlan) {
                $readingPlan->user->notify(
                    new ReadingPlanReminderNotification($readingPlan, 'due_today')
                );

                $readingPlan->update([
                    'reminder_due_sent_at' => now(),
                ]);
            });
    }
}
