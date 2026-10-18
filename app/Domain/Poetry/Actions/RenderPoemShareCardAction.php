<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\Poetry\Support\PoemShareCardRenderer;
use Domain\Share\Support\ShareCardCache;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\ShareCardTemplate;

class RenderPoemShareCardAction
{
    public function __construct(
        private readonly PoemShareCardRenderer $renderer,
    ) {}

    public function execute(
        string $slug,
        ShareCardFormat $format = ShareCardFormat::Square,
        ShareCardTemplate $template = ShareCardTemplate::Verse,
    ): string {
        $poem = Poem::query()
            ->with('user.profile')
            ->where('slug', $slug)
            ->where('status', PoemStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        $locale = in_array($poem->language, ['it', 'en'], true) ? $poem->language : config('app.locale');
        app()->setLocale($locale);

        $cacheKey = sprintf(
            'poem-share-card:v%d:%d:%s:%s:%s:%s:%s',
            ShareCardCache::VERSION,
            $poem->id,
            $poem->updated_at?->timestamp ?? 0,
            $poem->user?->profile?->updated_at?->timestamp ?? 0,
            $locale,
            $format->value,
            $template->value,
        );

        return ShareCardCache::remember($cacheKey, fn () => $this->renderer->render($poem, $format, $template));
    }
}
