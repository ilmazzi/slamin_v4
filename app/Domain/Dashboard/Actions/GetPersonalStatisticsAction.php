<?php

namespace Domain\Dashboard\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Engagement\Models\Follow;
use Domain\Engagement\Models\Like;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GetPersonalStatisticsAction
{
    /**
     * @return array{
     *     totals: array<string, int>,
     *     publications: array<string, int>,
     *     top_by_views: array<int, array<string, int|string>>,
     *     top_by_likes: array<int, array<string, int|string>>
     * }
     */
    public function execute(User $user): array
    {
        $summary = $this->summary($user);
        $queries = $this->publicContentQueries($user->id);

        return [
            ...$summary,
            'top_by_views' => $this->topContent($queries, 'views_count'),
            'top_by_likes' => $this->topContent($queries, 'likes_count'),
        ];
    }

    /**
     * @return array{
     *     totals: array<string, int>,
     *     publications: array<string, int>
     * }
     */
    public function summary(User $user): array
    {
        $queries = $this->publicContentQueries($user->id);
        $publicationCounts = [];
        $viewsReceived = 0;

        foreach ($queries as $type => $config) {
            $publicationCounts[$config['plural']] = (clone $config['query'])->count();
            $viewsReceived += (int) (clone $config['query'])->sum('views_count');
        }

        return [
            'totals' => [
                'views_received' => $viewsReceived,
                'likes_received' => $this->receivedEngagementCount(Like::query(), $user->id),
                'comments_received' => $this->receivedEngagementCount(Comment::query(), $user->id),
                'followers' => Follow::query()
                    ->where('followable_type', $user->getMorphClass())
                    ->where('followable_id', $user->id)
                    ->count(),
            ],
            'publications' => $publicationCounts,
        ];
    }

    /**
     * @param  Builder<Like>|Builder<Comment>  $query
     */
    private function receivedEngagementCount(Builder $query, int $userId): int
    {
        return $query
            ->where('user_id', '!=', $userId)
            ->whereHasMorph(
                $query->getModel() instanceof Like ? 'likeable' : 'commentable',
                [Poem::class, Article::class, Event::class, MediaItem::class],
                function (Builder $contentQuery, string $type) use ($userId): void {
                    $contentQuery->where('user_id', $userId);
                    $this->applyPublicScope($contentQuery, $type);
                },
            )
            ->count();
    }

    /**
     * @param  array<string, array{query: Builder<Model>, plural: string, path: string, uses_slug: bool}>  $queries
     * @return array<int, array<string, int|string>>
     */
    private function topContent(array $queries, string $metric): array
    {
        $items = collect();

        foreach ($queries as $type => $config) {
            $content = (clone $config['query'])
                ->withCount(['likes', 'comments'])
                ->orderByDesc($metric)
                ->orderByDesc('views_count')
                ->limit(5)
                ->get();

            $items = $items->concat($content->map(
                fn (Model $item): array => $this->formatContentItem($item, $type, $config),
            ));
        }

        return $items
            ->sortByDesc(fn (array $item): int => $item[$metric])
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @param  array{query: Builder<Model>, plural: string, path: string, uses_slug: bool}  $config
     * @return array<string, int|string>
     */
    private function formatContentItem(Model $item, string $type, array $config): array
    {
        $identifier = $config['uses_slug'] ? $item->getAttribute('slug') : $item->getKey();

        return [
            'type' => $type,
            'id' => (int) $item->getKey(),
            'title' => (string) $item->getAttribute('title'),
            'slug' => (string) ($item->getAttribute('slug') ?? ''),
            'url' => "/{$config['path']}/{$identifier}",
            'views_count' => (int) ($item->getAttribute('views_count') ?? 0),
            'likes_count' => (int) ($item->getAttribute('likes_count') ?? 0),
            'comments_count' => (int) ($item->getAttribute('comments_count') ?? 0),
        ];
    }

    private function applyPublicScope(Builder $query, string $type): void
    {
        match ($type) {
            Poem::class => $query
                ->where('status', PoemStatus::Published)
                ->where('is_public', true),
            Article::class => $query
                ->where('status', ArticleStatus::Published)
                ->where('is_public', true),
            Event::class => $query
                ->where('status', EventStatus::Published)
                ->where('is_public', true),
            MediaItem::class => $query
                ->where('status', MediaStatus::Ready)
                ->where('visibility', MediaVisibility::Public),
        };
    }

    /**
     * @return array<string, array{query: Builder<Model>, plural: string, path: string, uses_slug: bool}>
     */
    private function publicContentQueries(int $userId): array
    {
        return [
            'poem' => [
                'query' => Poem::query()
                    ->where('user_id', $userId)
                    ->where('status', PoemStatus::Published)
                    ->where('is_public', true),
                'plural' => 'poems',
                'path' => 'poems',
                'uses_slug' => true,
            ],
            'article' => [
                'query' => Article::query()
                    ->where('user_id', $userId)
                    ->where('status', ArticleStatus::Published)
                    ->where('is_public', true),
                'plural' => 'articles',
                'path' => 'articles',
                'uses_slug' => true,
            ],
            'event' => [
                'query' => Event::query()
                    ->where('user_id', $userId)
                    ->where('status', EventStatus::Published)
                    ->where('is_public', true),
                'plural' => 'events',
                'path' => 'events',
                'uses_slug' => true,
            ],
            'media' => [
                'query' => MediaItem::query()
                    ->where('user_id', $userId)
                    ->where('status', MediaStatus::Ready)
                    ->where('visibility', MediaVisibility::Public),
                'plural' => 'media',
                'path' => 'media',
                'uses_slug' => false,
            ],
        ];
    }
}
