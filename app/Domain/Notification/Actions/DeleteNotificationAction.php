<?php

namespace Domain\Notification\Actions;

use Domain\Notification\Models\Notification;

class DeleteNotificationAction
{
    public function execute(Notification $notification, int $userId): void
    {
        $oldValues = [
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toISOString(),
        ];

        $notification->delete();

        audit()->log(
            event: 'notification.deleted',
            userId: $userId,
            auditable: $notification,
            oldValues: $oldValues,
            metadata: [
                'source' => 'notification_delete',
            ],
        );
    }
}
