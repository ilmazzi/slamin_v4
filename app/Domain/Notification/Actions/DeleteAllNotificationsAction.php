<?php

namespace Domain\Notification\Actions;

use Domain\Notification\Models\Notification;

class DeleteAllNotificationsAction
{
    public function execute(int $userId): int
    {
        $deleted = Notification::query()
            ->where('user_id', $userId)
            ->delete();

        audit()->log(
            event: 'notification.deleted_all',
            userId: $userId,
            newValues: [
                'deleted_count' => $deleted,
            ],
            metadata: [
                'source' => 'notification_delete_all',
            ],
        );

        return $deleted;
    }
}
