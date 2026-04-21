<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\Event;

class DeleteEventAction
{
    public function execute(Event $event, int $userId): void
    {
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
