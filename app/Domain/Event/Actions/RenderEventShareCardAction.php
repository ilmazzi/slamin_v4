<?php

namespace Domain\Event\Actions;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Event\Support\EventShareCardRenderer;
use Illuminate\Support\Facades\Cache;

class RenderEventShareCardAction
{
    public function __construct(
        private readonly EventShareCardRenderer $renderer,
    ) {}

    public function execute(string $slug): string
    {
        $event = Event::query()
            ->with(['user.profile', 'occurrences', 'venueDetail'])
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        $locale = in_array($event->language, ['it', 'en'], true) ? $event->language : config('app.locale');
        app()->setLocale($locale);

        $primary = $event->occurrences->firstWhere('is_primary', true) ?? $event->occurrences->first();

        $cacheKey = sprintf(
            'event-share-card:v3:%d:%s:%s:%s:%s:%s',
            $event->id,
            $event->updated_at?->timestamp ?? 0,
            $event->user?->profile?->updated_at?->timestamp ?? 0,
            $event->cover_image_path ?? '',
            $primary?->updated_at?->timestamp ?? 0,
            $locale,
        );

        return Cache::remember($cacheKey, now()->addDay(), fn () => $this->renderer->render($event));
    }
}
