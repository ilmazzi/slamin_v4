<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Share\Support\GenericShareCardRenderer;
use Domain\Share\Support\ShareCardCache;
use Domain\Share\Support\ShareCardFormat;

class RenderMediaShareCardAction
{
    public function __construct(
        private readonly GenericShareCardRenderer $renderer,
    ) {}

    public function execute(int $id, ShareCardFormat $format = ShareCardFormat::Square): string
    {
        $media = MediaItem::query()
            ->with('user.profile')
            ->whereKey($id)
            ->where('status', MediaStatus::Ready)
            ->where('visibility', MediaVisibility::Public)
            ->firstOrFail();

        $cacheKey = sprintf(
            'media-share-card:v2:%d:%s:%s:%s',
            $media->id,
            $media->updated_at?->timestamp ?? 0,
            $media->user?->profile?->updated_at?->timestamp ?? 0,
            $format->value,
        );

        return ShareCardCache::remember($cacheKey, fn () => $this->renderer->render([
            'title' => $media->title ?: __('media.ui.untitled'),
            'subtitle' => $media->description,
            'author' => $media->user?->profile?->display_name,
            'image_path' => $media->type === MediaType::Photo ? $media->file_path : null,
            'background' => [0, 0, 0],
        ], $format));
    }
}
