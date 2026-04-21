<?php

namespace Domain\Notification\Actions;

use Domain\Notification\Models\Notification;

class CreateNotificationAction
{
    public function execute(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = [],
    ): Notification {
        return Notification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
