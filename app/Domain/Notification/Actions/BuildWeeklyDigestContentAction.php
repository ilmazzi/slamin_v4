<?php

namespace Domain\Notification\Actions;

use Carbon\CarbonInterface;
use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Notification\Support\NotificationMailLinks;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Database\Eloquent\Builder;

class BuildWeeklyDigestContentAction
{
    private const LIMIT = 3;

    /**
     * Shared by every recipient, so it is built once per run and handed to each job.
     *
     * @return array<string, list<array{title: string, url: string, likes: int, meta?: string}>>
     */
    public function execute(CarbonInterface $since): array
    {
        return [
            'poems' => $this->popular(
                Poem::query()->where('status', PoemStatus::Published)->where('is_public', true),
                $since,
                fn (Poem $poem) => NotificationMailLinks::frontend('poems/'.rawurlencode($poem->slug)),
            ),
            'articles' => $this->popular(
                Article::query()->where('status', ArticleStatus::Published)->where('is_public', true),
                $since,
                fn (Article $article) => NotificationMailLinks::frontend('articles/'.rawurlencode($article->slug)),
            ),
            'media' => $this->popular(
                MediaItem::query()->where('status', MediaStatus::Ready)->where('visibility', MediaVisibility::Public),
                $since,
                fn (MediaItem $media) => NotificationMailLinks::frontend('media/'.$media->id),
            ),
            'events' => $this->upcomingEvents($since),
        ];
    }

    private function popular(Builder $query, CarbonInterface $since, callable $url): array
    {
        return $query
            ->withCount(['likes as weekly_likes_count' => fn (Builder $likes) => $likes->where('created_at', '>=', $since)])
            ->where(fn (Builder $scope) => $scope
                ->whereHas('likes', fn (Builder $likes) => $likes->where('created_at', '>=', $since))
                ->orWhere('published_at', '>=', $since))
            ->orderByDesc('weekly_likes_count')
            ->orderByDesc('published_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($item) => [
                'title' => (string) ($item->title ?: __('media.ui.untitled')),
                'url' => $url($item),
                'likes' => (int) $item->weekly_likes_count,
            ])
            ->all();
    }

    private function upcomingEvents(CarbonInterface $since): array
    {
        $now = now();

        return Event::query()
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->whereHas('occurrences', fn (Builder $occurrences) => $occurrences->whereBetween('starts_at', [$now, $now->copy()->addWeeks(2)]))
            ->with(['occurrences' => fn ($occurrences) => $occurrences->where('starts_at', '>=', $now)->orderBy('starts_at')])
            ->withCount(['likes as weekly_likes_count' => fn (Builder $likes) => $likes->where('created_at', '>=', $since)])
            ->orderByDesc('weekly_likes_count')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Event $event) => [
                'title' => (string) $event->title,
                'url' => NotificationMailLinks::frontend('events/'.rawurlencode($event->slug)),
                'likes' => (int) $event->weekly_likes_count,
                'starts_at' => $event->occurrences->first()?->starts_at?->toIso8601String(),
                'timezone' => $event->occurrences->first()?->timezone,
            ])
            ->all();
    }
}
