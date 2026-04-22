<?php

namespace Domain\Event\Actions;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;

class ShowEventAction
{
    public function execute(string $slug, ?int $authUserId = null): Event
    {
        return Event::query()
            ->with([
                'user.profile',
                'category.translations',
                'festival',
                'occurrences',
                'recurrence',
                'availabilityPoll',
                'availabilityOptions.votes',
                'venueDetail',
                'onlineDetail',
                'groupLinks',
                'participantInvites.user.profile',
                'audienceInvites.user.profile',
            ])
            ->where('slug', $slug)
            ->where(function ($query) use ($authUserId) {
                if ($authUserId) {
                    $query->where('user_id', $authUserId)
                        ->orWhere(function ($query) {
                            $query->where('status', EventStatus::Published)
                                ->where('is_public', true);
                        });

                    return;
                }

                $query->where('status', EventStatus::Published)
                    ->where('is_public', true);
            })
            ->firstOrFail();
    }
}
