<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\StorePhotoMediaData;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StorePhotoMediaAction
{
    public function __construct(
        private readonly ResolveMediaSettingsAction $resolveMediaSettingsAction,
        private readonly ResolveUserMediaLimitsAction $resolveUserMediaLimitsAction,
    ) {
    }

    public function execute(StorePhotoMediaData $data): MediaItem
    {
        $settings = $this->resolveMediaSettingsAction->execute();
        $limits = $this->resolveUserMediaLimitsAction->execute($data->userId);

        if ($limits['remaining_photo_uploads'] <= 0) {
            throw ValidationException::withMessages([
                'file' => [__('media.validation.photo_limit_reached')],
            ]);
        }

        if (($data->file->getSize() / 1024) > (int) $settings['photo_max_size_kb']) {
            throw ValidationException::withMessages([
                'file' => [__('media.validation.photo_too_large')],
            ]);
        }

        if (! in_array($data->file->getMimeType(), (array) $settings['photo_allowed_mimes'], true)) {
            throw ValidationException::withMessages([
                'file' => [__('media.validation.photo_mime_not_allowed')],
            ]);
        }

        return DB::transaction(function () use ($data) {
            $path = $data->file->store('media/photos', 'public');
            [$width, $height] = @getimagesize($data->file->getRealPath()) ?: [null, null];

            $media = MediaItem::query()->create([
                'user_id' => $data->userId,
                'type' => MediaType::Photo,
                'title' => $data->title,
                'description' => $data->description,
                'visibility' => $data->visibility,
                'status' => MediaStatus::Ready,
                'mime_type' => $data->file->getMimeType(),
                'file_size' => $data->file->getSize() ?? 0,
                'uploaded_at' => now(),
                'published_at' => $data->visibility === MediaVisibility::Public ? now() : null,
                'file_path' => $path,
                'file_url' => Storage::disk('public')->url($path),
                'width' => $width,
                'height' => $height,
            ]);

            audit()->log(
                event: 'media.photo_uploaded',
                userId: $data->userId,
                auditable: $media,
                newValues: [
                    'media_id' => $media->id,
                    'type' => $media->type?->value,
                    'visibility' => $media->visibility?->value,
                ],
                metadata: [
                    'source' => 'media_photo_store',
                ],
            );

            return $media->load('user.profile');
        });
    }
}
