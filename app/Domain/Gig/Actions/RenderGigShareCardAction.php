<?php

namespace Domain\Gig\Actions;

use Domain\Event\Models\Event;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Domain\Gig\Support\GigShareCopy;
use Domain\Poetry\Models\Poem;
use Domain\Share\Support\GenericShareCardRenderer;
use Domain\Share\Support\ShareCardCache;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\ShareCardPalette;
use Domain\Share\Support\ShareCardTemplate;
use Domain\Share\Support\SharePage;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
            ->with(['giggable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Event::class => ['user.profile', 'venueDetail', 'occurrences'],
                    Poem::class => ['user.profile'],
                ]);
            }])
            ->whereKey($id)
            ->where('status', GigStatus::Open)
            ->withPublicParent()
            ->firstOrFail();

        $parent = $gig->giggable;
        $imagePath = $parent instanceof Event
            ? $parent->cover_image_path
            : ($parent instanceof Poem ? $parent->image_path : null);

        $locale = in_array($gig->language, ['it', 'en'], true)
            ? $gig->language
            : (in_array($parent?->language, ['it', 'en'], true) ? $parent->language : config('app.locale'));
        app()->setLocale($locale);

        $context = GigShareCopy::context($gig);
        $excerpt = SharePage::excerpt($gig->description);
        $subtitle = $context['parent_title'];
        if (is_string($excerpt) && $excerpt !== '' && ! str_contains($subtitle, $excerpt)) {
            $subtitle = trim($subtitle.' · '.$excerpt);
        }

        $cacheKey = sprintf(
            'gig-share-card:v%d:%d:%s:%s:%s:%s:%d:%s:%s:%s:%s',
            ShareCardCache::VERSION,
            $gig->id,
            $gig->updated_at?->timestamp ?? 0,
            $parent?->updated_at?->timestamp ?? 0,
            $imagePath ?? '',
            $gig->type?->value ?? '',
            max(1, (int) $gig->quantity),
            $gig->language ?? '',
            $context['when'],
            $format->value,
            $template->value,
        );

        return ShareCardCache::remember($cacheKey, fn () => $this->renderer->render([
            'title' => $context['seeking'],
            'subtitle' => $subtitle,
            'eyebrow' => $context['when'] !== '' ? mb_convert_case($context['when'], MB_CASE_TITLE, 'UTF-8') : ($gig->type?->value ? __('gigs.types.'.$gig->type->value) : ''),
            'author' => $context['where'],
            'image_path' => $imagePath,
            'background' => ShareCardPalette::for('gig'),
            'graphic' => [
                'weekday' => $context['weekday'],
                'date' => $context['date'],
                'headline' => $context['seeking'],
                'context' => $context['parent_title'],
                'city' => $context['city'],
                'place' => $context['place'],
                'kicker' => __('gigs.share_card.diagonal_kicker'),
                'lead' => __('gigs.share_card.diagonal_lead'),
                'site' => __('gigs.share_card.diagonal_site'),
                'domain' => __('gigs.share_card.diagonal_domain'),
            ],
        ], $format, $template));
    }
}
