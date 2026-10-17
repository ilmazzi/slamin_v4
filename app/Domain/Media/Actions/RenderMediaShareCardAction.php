<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Share\Support\GenericShareCardRenderer;
use Domain\Share\Support\ShareCardCache;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\ShareCardPalette;
use Domain\Share\Support\ShareCardTemplate;

class RenderMediaShareCardAction
{
    public function __construct(
        private readonly GenericShareCardRenderer $renderer,
    ) {}

    public function execute(
        int $id,
        ShareCardFormat $format = ShareCardFormat::Square,
        ShareCardTemplate $template = ShareCardTemplate::PhotoLogo,
    ): string {
        $media = MediaItem::query()
            ->with('user.profile')
            ->whereKey($id)
            ->where('status', MediaStatus::Ready)
            ->where('visibility', MediaVisibility::Public)
            ->firstOrFail();

        $imagePath = $media->type === MediaType::Photo ? $media->file_path : null;
        if ($template->usesImage() && ! $imagePath) {
            $template = ShareCardTemplate::Brand;
        }

        $cacheKey = sprintf(
            'media-share-card:v3:%d:%s:%s:%s:%s',
            $media->id,
            $media->updated_at?->timestamp ?? 0,
            $media->user?->profile?->updated_at?->timestamp ?? 0,
            $format->value,
            $template->value,
        );

        return ShareCardCache::remember($cacheKey, fn () => $this->renderer->render([
            'title' => $media->title ?: __('media.ui.untitled'),
            'subtitle' => $media->description,
            'author' => $media->user?->profile?->display_name,
            'image_path' => $imagePath,
            'background' => ShareCardPalette::for('media'),
        ], $format, $template));
    }
}
