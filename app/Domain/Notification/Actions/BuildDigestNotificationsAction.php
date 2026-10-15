<?php

namespace Domain\Notification\Actions;

use Carbon\CarbonInterface;
use Domain\Notification\Models\Notification;
use Domain\Notification\Support\NotificationMailLinks;
use Domain\User\Models\User;

class BuildDigestNotificationsAction
{
    private const LIMIT = 10;

    /**
     * @return list<array{title: string, message: string, url: string}>
     */
    public function execute(User $recipient, CarbonInterface $since): array
    {
        return Notification::query()
            ->where('user_id', $recipient->id)
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Notification $notification) => [
                'title' => $notification->title,
                'message' => $notification->message,
                'url' => NotificationMailLinks::forData($notification->data ?? []),
            ])
            ->all();
    }
}
