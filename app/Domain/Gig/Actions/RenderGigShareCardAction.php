<?php

namespace Domain\Gig\Actions;

use Domain\Event\Models\Event;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Domain\Poetry\Models\Poem;
use Domain\Share\Support\GenericShareCardRenderer;
use Domain\Share\Support\ShareCardCache;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\ShareCardPalette;
use Domain\Share\Support\ShareCardTemplate;
use Domain\Share\Support\SharePage;

class RenderGigShareCardAction
{
    public function __construct(
        private readonly GenericShareCardRenderer $renderer,
    ) {}

    public function execute(
        int $id,
        ShareCardFormat $format = ShareCardFormat::Square,
        ShareCardTemplate $template = ShareCardTemplate::Promo,
    ): string {
        $gig = Gig::query()
            ->with(['giggable.user.profile'])
            ->whereKey($id)
            ->where('status', GigStatus::Open)
            ->withPublicParent()
            ->firstOrFail();

        $parent = $gig->giggable;
        $imagePath = $parent instanceof Event
            ? $parent->cover_image_path
            : ($parent instanceof Poem ? $parent->image_path : null);

        if ($template->usesImage() && ! $imagePath) {
            $template = ShareCardTemplate::Typographic;
        }

        $locale = in_array($gig->language, ['it', 'en'], true)
            ? $gig->language
            : (in_array($parent?->language, ['it', 'en'], true) ? $parent->language : config('app.locale'));
        app()->setLocale($locale);

        $cacheKey = sprintf(
            'gig-share-card:v1:%d:%s:%s:%s:%s:%s:%s',
            $gig->id,
            $gig->updated_at?->timestamp ?? 0,
            $parent?->updated_at?->timestamp ?? 0,
            $imagePath ?? '',
            $gig->type?->value ?? '',
            $format->value,
            $template->value,
        );

        return ShareCardCache::remember($cacheKey, fn () => $this->renderer->render([
            'title' => trim((string) ($parent?->title ?? '')) ?: __('gigs.card.untitled'),
            'subtitle' => SharePage::excerpt($gig->description),
            'eyebrow' => $gig->type?->value ? __('gigs.types.'.$gig->type->value) : __('gigs.card.untitled'),
            'author' => $parent?->user?->profile?->display_name,
            'image_path' => $imagePath,
            'background' => ShareCardPalette::for('gig'),
        ], $format, $template));
    }
}
