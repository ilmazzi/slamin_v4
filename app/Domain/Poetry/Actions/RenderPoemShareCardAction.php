<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\Poetry\Support\PoemShareCardRenderer;
use Illuminate\Support\Facades\Cache;

class RenderPoemShareCardAction
{
    public function __construct(
        private readonly PoemShareCardRenderer $renderer,
    ) {}

    public function execute(string $slug): string
    {
        $poem = Poem::query()
            ->with('user.profile')
            ->where('slug', $slug)
            ->where('status', PoemStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        $locale = in_array($poem->language, ['it', 'en'], true) ? $poem->language : config('app.locale');
        app()->setLocale($locale);

        $cacheKey = sprintf(
            'poem-share-card:v4:%d:%s:%s:%s',
            $poem->id,
            $poem->updated_at?->timestamp ?? 0,
            $poem->user?->profile?->updated_at?->timestamp ?? 0,
            $locale,
        );

        return Cache::remember($cacheKey, now()->addDay(), fn () => $this->renderer->render($poem));
    }
}
