<?php

namespace Domain\Notification\Actions;

use App\Events\NotificationCreated;
use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Jobs\SendNotificationEmailJob;
use Domain\Notification\Models\Notification;
use Illuminate\Support\Facades\DB;

class CreateNotificationAction
{
    public function execute(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = [],
    ): Notification {
        $notification = Notification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        // A realtime outage must never roll back or fail the action that produced the notification.
        DB::afterCommit(fn () => rescue(fn () => NotificationCreated::dispatch($notification)));

        if (NotificationCategory::fromType($type)?->isMailedImmediately()) {
            SendNotificationEmailJob::dispatch($notification->id)->afterCommit();
        }

        return $notification;
    }
}
