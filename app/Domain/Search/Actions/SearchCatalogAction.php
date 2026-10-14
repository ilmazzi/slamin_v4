<?php

namespace Domain\Search\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SearchCatalogAction
{
    private const LIMIT = 6;

    /**
     * @return array{
     *     query: string,
     *     groups: list<array{type: string, href: string|null, items: list<array<string, mixed>>}>
     * }
     */
    public function execute(string $query): array
    {
        $query = trim($query);
        $term = $this->likeTerm($query);

        return [
            'query' => $query,
            'groups' => [
                $this->group('people', null, $this->searchPeople($term)),
                $this->group('events', '/events', $this->searchEvents($term)),
                $this->group('poems', '/poems?q='.rawurlencode($query), $this->searchPoems($term)),
                $this->group('articles', '/articles', $this->searchArticles($term)),
                $this->group('media', '/media?q='.rawurlencode($query), $this->searchMedia($term)),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{type: string, href: string|null, items: list<array<string, mixed>>}
     */
    private function group(string $type, ?string $href, array $items): array
    {
        return [
            'type' => $type,
            'href' => $href,
            'items' => $items,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchPeople(string $term): array
    {
        return User::query()
            ->with('profile')
            ->active()
            ->whereNotNull('email_verified_at')
            ->whereHas('profile', function (Builder $profileQuery) use ($term): void {
                $profileQuery
                    ->where('is_public', true)
                    ->where(function (Builder $nameQuery) use ($term): void {
                        $nameQuery
                            ->whereRaw('LOWER(nickname) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(display_name) LIKE ?', [$term]);
                    });
            })
            ->limit(self::LIMIT)
            ->get()
            ->map(function (User $user): array {
                $nickname = $user->profile?->nickname;
                $displayName = $user->profile?->display_name;

                return $this->hit(
                    type: 'person',
                    id: $user->id,
                    title: $displayName ?: ($nickname ? '@'.$nickname : ''),
                    subtitle: $displayName && $nickname ? '@'.$nickname : null,
                    href: $nickname ? '/u/'.$nickname : null,
                    imageUrl: $user->profile?->avatar_path
                        ? Storage::disk('public')->url($user->profile->avatar_path)
                        : null,
                );
            })
            ->filter(fn (array $hit): bool => filled($hit['href']))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchEvents(string $term): array
    {
        return Event::query()
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->where(function (Builder $builder) use ($term): void {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(subtitle) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get(['id', 'title', 'subtitle', 'slug', 'cover_image_path'])
            ->map(fn (Event $event): array => $this->hit(
                type: 'event',
                id: $event->id,
                title: (string) $event->title,
                subtitle: $event->subtitle,
                href: $event->slug ? '/events/'.$event->slug : null,
                imageUrl: $event->cover_image_path
                    ? Storage::disk('public')->url($event->cover_image_path)
                    : null,
            ))
            ->filter(fn (array $hit): bool => filled($hit['href']))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchPoems(string $term): array
    {
        return Poem::query()
            ->with('user.profile')
            ->where('status', PoemStatus::Published)
            ->where('is_public', true)
            ->where(function (Builder $builder) use ($term): void {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Poem $poem): array => $this->hit(
                type: 'poem',
                id: $poem->id,
                title: (string) $poem->title,
                subtitle: $poem->user?->profile?->display_name
                    ?: ($poem->user?->profile?->nickname ? '@'.$poem->user->profile->nickname : null),
                href: $poem->slug ? '/poems/'.$poem->slug : null,
                imageUrl: $poem->image_path
                    ? Storage::disk('public')->url($poem->image_path)
                    : null,
            ))
            ->filter(fn (array $hit): bool => filled($hit['href']))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchArticles(string $term): array
    {
        return Article::query()
            ->where('status', ArticleStatus::Published)
            ->where('is_public', true)
            ->where(function (Builder $builder) use ($term): void {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(excerpt) LIKE ?', [$term]);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get(['id', 'title', 'excerpt', 'slug', 'cover_image_path'])
            ->map(fn (Article $article): array => $this->hit(
                type: 'article',
                id: $article->id,
                title: (string) $article->title,
                subtitle: $article->excerpt,
                href: $article->slug ? '/articles/'.$article->slug : null,
                imageUrl: $article->cover_image_path
                    ? Storage::disk('public')->url($article->cover_image_path)
                    : null,
            ))
            ->filter(fn (array $hit): bool => filled($hit['href']))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchMedia(string $term): array
    {
        return MediaItem::query()
            ->where('status', MediaStatus::Ready)
            ->where('visibility', MediaVisibility::Public)
            ->where(function (Builder $builder) use ($term): void {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (MediaItem $media): array => $this->hit(
                type: 'media',
                id: $media->id,
                title: (string) $media->title,
                subtitle: $media->description,
                href: '/media/'.$media->id,
                imageUrl: $media->peertube_thumbnail_url
                    ?: ($media->file_path ? Storage::disk('public')->url($media->file_path) : $media->file_url),
            ))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function hit(
        string $type,
        int $id,
        string $title,
        ?string $subtitle,
        ?string $href,
        ?string $imageUrl,
    ): array {
        return [
            'type' => $type,
            'id' => $id,
            'title' => $title,
            'subtitle' => $subtitle ? Str::limit(trim(strip_tags($subtitle)), 120) : null,
            'href' => $href,
            'image_url' => $imageUrl,
        ];
    }

    private function likeTerm(string $query): string
    {
        return '%'.addcslashes(mb_strtolower($query), '%_\\').'%';
    }
}
