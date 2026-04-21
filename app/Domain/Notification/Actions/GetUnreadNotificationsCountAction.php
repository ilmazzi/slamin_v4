<?php

namespace Domain\Notification\Actions;

use Domain\Notification\Models\Notification;

class GetUnreadNotificationsCountAction
{
    public function execute(int $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
