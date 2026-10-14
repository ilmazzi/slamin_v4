<?php

namespace Domain\Moderation\Actions;

use Domain\Event\Models\Event;

class ShowModerationEventAction
{
    public function execute(Event $event): Event
    {
        return $event->load([
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
            'moderationDecisions.moderator.profile',
        ]);
    }
}
