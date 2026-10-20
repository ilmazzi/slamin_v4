<?php

namespace Domain\Seo\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\Profile\Models\Profile;
use Illuminate\Support\Carbon;

class BuildSitemapAction
{
    /**
     * @return list<array{loc: string, lastmod: string}>
     */
    public function execute(): array
    {
        $urls = $this->staticPages();

        $this->appendPublishedContent($urls, Event::query()
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->whereNotNull('slug')
            ->orderBy('id'), 'events');

        $this->appendPublishedContent($urls, Poem::query()
            ->where('status', PoemStatus::Published)
            ->where('is_public', true)
            ->whereNotNull('slug')
            ->orderBy('id'), 'poems');

        $this->appendPublishedContent($urls, Article::query()
            ->where('status', ArticleStatus::Published)
            ->where('is_public', true)
            ->whereNotNull('slug')
            ->orderBy('id'), 'articles');

        MediaItem::query()
            ->where('status', MediaStatus::Ready)
            ->where('visibility', MediaVisibility::Public)
            ->orderBy('id')
            ->lazyById()
            ->each(function (MediaItem $media) use (&$urls): void {
                $urls[] = $this->entry('/media/'.$media->id, $media->updated_at ?? $media->published_at);
            });

        Profile::query()
            ->where('is_public', true)
            ->whereNotNull('nickname')
            ->where('nickname', '!=', '')
            ->whereHas('user', fn ($query) => $query->active())
            ->orderBy('id')
            ->lazyById()
            ->each(function (Profile $profile) use (&$urls): void {
                $urls[] = $this->entry('/u/'.rawurlencode((string) $profile->nickname), $profile->updated_at);
            });

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod: string}>
     */
    private function staticPages(): array
    {
        $now = now();

        return [
            $this->entry('/', $now),
            $this->entry('/events', $now),
            $this->entry('/poems', $now),
            $this->entry('/articles', $now),
            $this->entry('/media', $now),
            $this->entry('/search', $now),
            $this->entry('/help', $now),
            $this->entry('/faq', $now),
            $this->entry('/support', $now),
            $this->entry('/contact', $now),
            $this->entry('/about', $now),
            $this->entry('/terms', $now),
            $this->entry('/privacy', $now),
            $this->entry('/cookies', $now),
            $this->entry('/newsletter', $now),
        ];
    }

    /**
     * @param  list<array{loc: string, lastmod: string}>  $urls
     */
    private function appendPublishedContent(array &$urls, mixed $query, string $segment): void
    {
        $query->lazyById()->each(function (Event|Poem|Article $item) use (&$urls, $segment): void {
            $slug = trim((string) $item->slug);
            if ($slug === '') {
                return;
            }

            $urls[] = $this->entry('/'.$segment.'/'.$slug, $item->updated_at ?? $item->published_at);
        });
    }

    /**
     * @return array{loc: string, lastmod: string}
     */
    private function entry(string $path, mixed $lastmod): array
    {
        $timestamp = $lastmod instanceof Carbon ? $lastmod : now();

        return [
            'loc' => $this->publicUrl($path),
            'lastmod' => $timestamp->copy()->utc()->toAtomString(),
        ];
    }

    private function publicUrl(string $path): string
    {
        return rtrim((string) (config('app.frontend_url') ?: config('app.url')), '/').$path;
    }
}
