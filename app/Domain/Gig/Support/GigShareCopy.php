<?php

namespace Domain\Gig\Support;

use Domain\Event\Enums\EventDeliveryMode;
use Domain\Event\Models\Event;
use Domain\Gig\Models\Gig;
use Domain\Poetry\Models\Poem;

final class GigShareCopy
{
    public static function seeking(Gig $gig): string
    {
        $count = max(1, (int) $gig->quantity);
        $type = $gig->type?->value ?? 'poet_artist';
        $form = $count === 1 ? 'one' : 'other';
        $role = __("gigs.share_card.roles.{$type}.{$form}");
        if (! is_string($role) || $role === "gigs.share_card.roles.{$type}.{$form}") {
            $role = (string) __('gigs.types.'.$type);
        }

        $line = trans_choice('gigs.share_card.seeking', $count, [
            'count' => $count,
            'role' => $role,
        ]);

        if ($type === 'translator' && filled($gig->language)) {
            $line .= ' · '.$gig->language;
        }

        return $line;
    }

    /**
     * @return array{
     *     seeking: string,
     *     parent_title: string,
     *     weekday: string,
     *     date: string,
     *     when: string,
     *     city: string,
     *     place: string,
     *     where: string
     * }
     */
    public static function context(Gig $gig): array
    {
        $parent = $gig->giggable;
        $parentTitle = trim((string) ($parent?->title ?? '')) ?: (string) __('gigs.card.untitled');
        $weekday = '';
        $date = '';
        $city = '';
        $place = '';

        if ($parent instanceof Event) {
            $occurrence = $parent->relationLoaded('occurrences')
                ? ($parent->occurrences->firstWhere('is_primary', true) ?? $parent->occurrences->sortBy('starts_at')->first())
                : null;
            if ($occurrence?->starts_at !== null) {
                $local = $occurrence->starts_at
                    ->timezone($occurrence->timezone ?: 'UTC')
                    ->locale(app()->getLocale());
                $weekday = mb_strtolower($local->translatedFormat('l'));
                $date = mb_strtolower($local->translatedFormat('j F'));
            }

            if ($parent->delivery_mode === EventDeliveryMode::Online) {
                $place = (string) __('events.share_card.online_label');
            } elseif ($parent->relationLoaded('venueDetail') && $parent->venueDetail !== null) {
                $city = trim((string) $parent->venueDetail->city);
                $place = trim((string) $parent->venueDetail->place_name);
            }
        }

        if ($parent instanceof Poem && $place === '') {
            $place = $parentTitle;
        }

        return [
            'seeking' => self::seeking($gig),
            'parent_title' => $parentTitle,
            'weekday' => $weekday,
            'date' => $date,
            'when' => trim($weekday.' '.$date),
            'city' => $city,
            'place' => $place,
            'where' => implode(' · ', array_values(array_filter([$place, $city]))),
        ];
    }
}
