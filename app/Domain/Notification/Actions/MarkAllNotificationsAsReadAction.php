<?php

namespace Domain\Notification\Actions;

use Domain\Notification\Models\Notification;

class MarkAllNotificationsAsReadAction
{
    public function execute(int $userId): int
    {
        $updated = Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        audit()->log(
            event: 'notification.read_all',
            userId: $userId,
            newValues: [
                'updated_count' => $updated,
            ],
            metadata: [
                'source' => 'notification_mark_all_read',
            ],
        );

        return $updated;
    }
}
