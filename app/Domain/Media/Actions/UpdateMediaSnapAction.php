<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\UpdateMediaSnapData;
use Domain\Media\Models\MediaSnap;
use Illuminate\Validation\ValidationException;

class UpdateMediaSnapAction
{
    public function execute(MediaSnap $snap, UpdateMediaSnapData $data): MediaSnap
    {
        if ($data->timestampSeconds !== null) {
            $this->assertVideoTimestampIsValid($snap->mediaItem->duration_seconds, $data->timestampSeconds);
            $snap->timestamp_seconds = $data->timestampSeconds;
        }

        if ($data->kind !== null) {
            $snap->kind = $data->kind;
        }

        if ($data->comment !== null) {
            $snap->comment = $data->comment;
        }

        $snap->save();

        return $snap->fresh(['user.profile', 'mediaItem']);
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
