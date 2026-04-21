<?php

namespace Domain\Moderation\Actions;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Moderation\DTOs\ListModerationEventsData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListModerationEventsAction
{
    public function execute(ListModerationEventsData $data): LengthAwarePaginator
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
            ->orderByRaw(
                "case when status = ? then 0 when status = ? then 1 else 2 end",
                [EventStatus::PendingReview->value, EventStatus::Rejected->value]
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');

        if ($data->status) {
            $query->where('status', $data->status);
        }

        if ($data->query) {
            $search = $data->query;
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user.profile', fn ($q) => $q
                        ->where('nickname', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%"));
            });
        }

        if ($data->eventCategoryId) {
            $query->where('event_category_id', $data->eventCategoryId);
        }

        if ($data->festivalEventId) {
            $query->where('festival_event_id', $data->festivalEventId);
        }

        return $query->paginate($data->perPage);
    }
}
