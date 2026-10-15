<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaItem;
use Domain\Notification\Actions\CreateNotificationAction;

class NotifyMediaVideoFailedAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(MediaItem $media): void
    {
        $this->createNotificationAction->execute(
            userId: $media->user_id,
            type: 'media_video_failed',
            title: fn () => __('media.notifications.video_failed_title'),
            message: fn () => __('media.notifications.video_failed_message', [
                'title' => $media->title,
            ]),
            data: [
                'media_item_id' => $media->id,
                'status' => $media->status?->value,
                'processing_status' => $media->processing_status,
                'upload_error' => $media->upload_error,
            ],
        );
    }
}
