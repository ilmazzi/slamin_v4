<?php

namespace Domain\Event\Actions;

use Domain\Event\DTOs\ListEventData;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListEventAction
{
    public function execute(ListEventData $data): LengthAwarePaginator
    {
        $query = Event::query()
            ->with([
                'user.profile',
                'category.translations',
                'festival',
                'occurrences',
                'venueDetail',
                'onlineDetail',
            ])
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        if ($data->eventCategoryId) {
            $query->where('event_category_id', $data->eventCategoryId);
        }

        if ($data->festivalEventId) {
            $query->where('festival_event_id', $data->festivalEventId);
        }

        return $query->paginate($data->perPage);
    }
}
