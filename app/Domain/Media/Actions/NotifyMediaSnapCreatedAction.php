<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaSnap;
use Domain\Notification\Actions\CreateNotificationAction;

class NotifyMediaSnapCreatedAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(MediaSnap $snap): void
    {
        $snap->loadMissing(['user.profile', 'mediaItem']);

        $media = $snap->mediaItem;

        if (! $media || $media->user_id === $snap->user_id) {
            return;
        }

        $senderName = $snap->user?->profile?->display_name
            ?? $snap->user?->profile?->nickname
            ?? $snap->user?->email;

        $this->createNotificationAction->execute(
            userId: $media->user_id,
            type: 'media_snap_created',
            title: fn () => __('media.notifications.snap_created_title'),
            message: fn () => __('media.notifications.snap_created_message', [
                'user' => $senderName ?? __('notifications.someone'),
                'title' => $media->title,
                'second' => $snap->timestamp_seconds,
                'kind' => $snap->kind ? __('media.notifications.snap_kinds.'.$snap->kind->value) : '',
            ]),
            data: [
                'media_item_id' => $media->id,
                'media_snap_id' => $snap->id,
                'sender_user_id' => $snap->user_id,
                'timestamp_seconds' => $snap->timestamp_seconds,
                'kind' => $snap->kind?->value,
            ],
        );
    }
}
