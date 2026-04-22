<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\StoreMediaSnapData;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\MediaSnap;
use Illuminate\Validation\ValidationException;

class StoreMediaSnapAction
{
    public function __construct(
        private readonly NotifyMediaSnapCreatedAction $notifyMediaSnapCreatedAction,
    ) {
    }

    public function execute(MediaItem $media, int $userId, StoreMediaSnapData $data): MediaSnap
    {
        $this->assertVideoTimestampIsValid($media->duration_seconds, $data->timestampSeconds);

        $snap = MediaSnap::query()->create([
            'media_item_id' => $media->id,
            'user_id' => $userId,
            'timestamp_seconds' => $data->timestampSeconds,
            'kind' => $data->kind,
            'comment' => $data->comment,
        ])->load(['user.profile', 'mediaItem']);

        $this->notifyMediaSnapCreatedAction->execute($snap);

        return $snap;
    }

    private function assertVideoTimestampIsValid(?int $durationSeconds, int $timestampSeconds): void
    {
        if ($timestampSeconds < 0) {
            throw ValidationException::withMessages([
                'timestamp_seconds' => __('media.validation.invalid_snap_timestamp'),
            ]);
        }

        if ($durationSeconds !== null && $timestampSeconds > $durationSeconds) {
            throw ValidationException::withMessages([
                'timestamp_seconds' => __('media.validation.snap_timestamp_exceeds_duration'),
            ]);
        }
    }
}
