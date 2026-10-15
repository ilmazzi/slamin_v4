<?php

namespace Domain\Event\Actions;

use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventAvailabilityOption;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FinalizeEventAvailabilityOptionAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Event $event, EventAvailabilityOption $option, int $userId): Event
    {
        return DB::transaction(function () use ($event, $option, $userId) {
            $this->guard($event, $option);

            $event->availabilityOptions()->update(['finalized_at' => null]);

            $option->update([
                'finalized_at' => now(),
            ]);

            $event->occurrences()->delete();
            $event->occurrences()->create([
                'starts_at' => $option->starts_at,
                'ends_at' => $option->ends_at,
                'timezone' => $option->timezone,
                'is_primary' => true,
            ]);

            audit()->log(
                event: 'event_availability_option.finalized',
                userId: $userId,
                auditable: $event,
                newValues: [
                    'availability_option_id' => $option->id,
                    'starts_at' => $option->starts_at?->toISOString(),
                    'ends_at' => $option->ends_at?->toISOString(),
                    'timezone' => $option->timezone,
                ],
                metadata: [
                    'source' => 'event_availability_finalize',
                ],
            );

            $this->notifyInvitees($event, $option);

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
    }

    private function notifyInvitees(Event $event, EventAvailabilityOption $option): void
    {
        $recipientIds = $event->participantInvites()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->merge($event->audienceInvites()->pluck('user_id'))
            ->filter()
            ->reject(fn ($id) => (int) $id === (int) $event->user_id)
            ->unique()
            ->values();

        foreach ($recipientIds as $recipientId) {
            $this->createNotificationAction->execute(
                userId: (int) $recipientId,
                type: 'event_availability_finalized',
                title: fn () => __('event.notifications.availability_finalized_title'),
                message: fn () => __('event.notifications.availability_finalized_message', [
                    'title' => $event->title,
                    'starts_at' => $option->starts_at?->timezone($option->timezone)->format('Y-m-d H:i'),
                    'timezone' => $option->timezone,
                ]),
                data: [
                    'event_id' => $event->id,
                    'event_slug' => $event->slug,
                    'availability_option_id' => $option->id,
                    'starts_at' => $option->starts_at?->toISOString(),
                    'ends_at' => $option->ends_at?->toISOString(),
                    'timezone' => $option->timezone,
                ],
            );
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
