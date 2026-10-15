<?php

namespace Domain\Notification\Actions;

use Carbon\CarbonInterface;
use Domain\Engagement\Actions\NotifyContentLikedAction;
use Domain\Notification\Models\Notification;
use Domain\Notification\Support\NotificationMailLinks;
use Domain\User\Models\User;

class BuildLikesSummaryAction
{
    private const NAMES_PER_ITEM = 3;

    /**
     * @return array{items: list<array{title: string, url: string, count: int, names: list<string>}>, total: int}
     */
    public function execute(User $recipient, CarbonInterface $since, CarbonInterface $until): array
    {
        $notifications = Notification::query()
            ->where('user_id', $recipient->id)
            ->whereIn('type', NotifyContentLikedAction::TYPES)
            ->whereBetween('created_at', [$since, $until])
            ->latest()
            ->get();

        $items = $notifications
            ->groupBy(fn (Notification $notification) => $notification->type.':'.($notification->data['content_id'] ?? $notification->id))
            ->map(fn ($group) => [
                'title' => (string) ($group->first()->data['content_title'] ?? ''),
                'url' => NotificationMailLinks::forData($group->first()->data ?? []),
                'count' => $group->count(),
                'names' => $group->pluck('data.liker_name')->filter()->unique()->take(self::NAMES_PER_ITEM)->values()->all(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        return ['items' => $items, 'total' => $notifications->count()];
    }
}
