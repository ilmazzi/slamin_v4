<?php

namespace Domain\Event\Queries;

use Domain\Event\DTOs\ListEventData;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Shared\Support\AuthorNicknameFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListEventQuery
{
    public function execute(ListEventData $data): LengthAwarePaginator
    {
        $query = Event::query()
            ->with([
                'category.translations',
                'occurrences',
                'venueDetail',
            ])
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->withEngagement(auth('sanctum')->id())
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        AuthorNicknameFilter::apply($query, $data->nickname);

        if ($data->eventCategoryId) {
            $query->where('event_category_id', $data->eventCategoryId);
        }

        if ($data->festivalEventId) {
            $query->where('festival_event_id', $data->festivalEventId);
        }

        return $query->paginate($data->perPage);
    }
}
