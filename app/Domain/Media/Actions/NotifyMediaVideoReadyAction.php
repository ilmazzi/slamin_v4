<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaItem;
use Domain\Notification\Actions\CreateNotificationAction;

class NotifyMediaVideoReadyAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(MediaItem $media): void
    {
        $this->createNotificationAction->execute(
            userId: $media->user_id,
            type: 'media_video_ready',
            title: fn () => __('media.notifications.video_ready_title'),
            message: fn () => __('media.notifications.video_ready_message', [
                'title' => $media->title,
            ]),
            data: [
                'media_item_id' => $media->id,
                'status' => $media->status?->value,
                'processing_status' => $media->processing_status,
            ],
        );
    }
}
