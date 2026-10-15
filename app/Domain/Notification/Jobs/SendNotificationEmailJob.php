<?php

namespace Domain\Notification\Jobs;

use App\Mail\Notifications\NotificationMail;
use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $notificationId,
    ) {}

    public function handle(): void
    {
        $notification = Notification::query()->with('user.profile')->find($this->notificationId);
        $category = $notification ? NotificationCategory::fromType($notification->type) : null;
        $recipient = $notification?->user;

        if ($category === null || $recipient === null || ! $recipient->wantsNotificationEmail($category)) {
            return;
        }

        Mail::to($recipient)->send(new NotificationMail($recipient, $category, $notification));
    }
}
