<?php

namespace Domain\Event\Queries;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;

class ShowEventQuery
{
    public function execute(string $slug, ?int $authUserId = null): Event
    {
        return Event::query()
            ->with(Event::RESOURCE_RELATIONS)
            ->withEngagement($authUserId)
            ->withFollowState($authUserId)
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
