<?php

namespace Domain\Notification\Actions;

use Domain\Notification\Models\Notification;

class MarkNotificationAsReadAction
{
    public function execute(Notification $notification, int $userId): Notification
    {
        if (! $notification->read_at) {
            $notification->update([
                'read_at' => now(),
            ]);

            audit()->log(
                event: 'notification.read',
                userId: $userId,
                auditable: $notification,
                newValues: [
                    'read_at' => $notification->read_at?->toISOString(),
                ],
                metadata: [
                    'source' => 'notification_mark_read',
                ],
            );
        }

        return $notification->fresh();
    }
}
