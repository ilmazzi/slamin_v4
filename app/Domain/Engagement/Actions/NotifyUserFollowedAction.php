<?php

namespace Domain\Engagement\Actions;

use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Notification\Models\Notification;
use Domain\User\Models\User;

class NotifyUserFollowedAction
{
    private const TYPE = 'user_followed';

    /**
     * Following again soon after an unfollow must not notify the same person twice.
     */
    private const REPEAT_WINDOW_HOURS = 24;

    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(User $followed, int $followerId): void
    {
        $alreadyNotified = Notification::query()
            ->where('user_id', $followed->id)
            ->where('type', self::TYPE)
            ->where('data->follower_user_id', $followerId)
            ->where('created_at', '>=', now()->subHours(self::REPEAT_WINDOW_HOURS))
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $profile = User::query()->with('profile:id,user_id,nickname,display_name')->find($followerId, ['id'])?->profile;
        $name = $profile?->display_name ?: $profile?->nickname;

        $this->createNotificationAction->execute(
            userId: $followed->id,
            type: self::TYPE,
            title: fn () => __('notifications.types.user_followed.title'),
            message: fn () => __('notifications.types.user_followed.message', ['name' => $name ?: __('notifications.someone')]),
            data: [
                'follower_user_id' => $followerId,
                'follower_nickname' => $profile?->nickname,
            ],
        );
    }
}
