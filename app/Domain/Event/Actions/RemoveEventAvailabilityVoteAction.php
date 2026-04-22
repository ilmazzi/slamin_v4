<?php

namespace Domain\Event\Actions;

use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventAvailabilityOption;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RemoveEventAvailabilityVoteAction
{
    public function execute(Event $event, EventAvailabilityOption $option, int $userId): Event
    {
        return DB::transaction(function () use ($event, $option, $userId) {
            $this->guard($event, $option);

            $deleted = $option->votes()
                ->where('user_id', $userId)
                ->delete();

            if ($deleted > 0) {
                audit()->log(
                    event: 'event_availability_option.unvoted',
                    userId: $userId,
                    auditable: $event,
                    oldValues: [
                        'availability_option_id' => $option->id,
                    ],
                    metadata: [
                        'source' => 'event_availability_unvote',
                    ],
                );
            }

            return $this->loadFullEvent($event);
        });
    }

    private function guard(Event $event, EventAvailabilityOption $option): void
    {
        if ($event->schedule_mode !== EventScheduleMode::Availability) {
            throw new HttpException(422, __('event.errors.availability_only'));
        }

        if ($option->event_id !== $event->id) {
            throw new HttpException(404);
        }

        if ($event->availabilityOptions()->whereNotNull('finalized_at')->exists()) {
            throw new HttpException(422, __('event.errors.availability_already_finalized'));
        }
    }

    private function loadFullEvent(Event $event): Event
    {
        return $event->fresh([
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
        ]);
    }
}
