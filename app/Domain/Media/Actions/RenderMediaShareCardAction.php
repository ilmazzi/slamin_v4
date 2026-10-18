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

        $title = $media->title ?: __('media.ui.untitled');
        $cacheKey = sprintf(
            'media-share-card:v%d:%d:%s:%s:%s:%s',
            ShareCardCache::VERSION,
            $media->id,
            $media->updated_at?->timestamp ?? 0,
            $media->user?->profile?->updated_at?->timestamp ?? 0,
            $format->value,
            $template->value,
        );

        return ShareCardCache::remember($cacheKey, fn () => $this->renderer->render([
            'title' => $title,
            'subtitle' => $media->description,
            'author' => $media->user?->profile?->display_name,
            'image_path' => $imagePath,
            'background' => ShareCardPalette::for('media'),
            'graphic' => [
                'headline' => $title,
                'city' => trim((string) $media->user?->profile?->display_name),
                'place' => trim((string) $media->description),
                'kicker' => $title,
                'lead' => trim((string) $media->description) ?: $title,
                'domain' => 'slamin.it',
            ],
        ], $format, $template));
    }
}
