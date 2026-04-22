<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\Event;
use Illuminate\Validation\ValidationException;

class DeleteEventAction
{
    public function execute(Event $event, int $userId): void
    {
        if ($event->childFestivalEvents()->exists()) {
            throw ValidationException::withMessages([
                'event' => __('event.validation.festival_with_children_cannot_be_deleted'),
            ]);
        }
    
        $event->delete();

        audit()->log(
            event: 'event.deleted',
            userId: $userId,
            auditable: $event,
            metadata: [
                'source' => 'event_delete',
            ],
        );
    }
}
