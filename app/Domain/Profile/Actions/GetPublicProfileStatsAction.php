<?php

namespace Domain\Profile\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;

class GetPublicProfileStatsAction
{
    /**
     * @param  list<string>  $sections
     * @return array<string, int>
     */
    public function execute(int $userId, array $sections): array
    {
        $stats = [];

        foreach ($sections as $section) {
            $stats[$section] = match ($section) {
                'poems' => Poem::query()
                    ->where('user_id', $userId)
                    ->where('status', PoemStatus::Published)
                    ->where('is_public', true)
                    ->count(),
                'events' => Event::query()
                    ->where('user_id', $userId)
                    ->where('status', EventStatus::Published)
                    ->where('is_public', true)
                    ->count(),
                'articles' => Article::query()
                    ->where('user_id', $userId)
                    ->where('status', ArticleStatus::Published)
                    ->where('is_public', true)
                    ->count(),
                'media' => MediaItem::query()
                    ->where('user_id', $userId)
                    ->where('status', MediaStatus::Ready)
                    ->where('visibility', MediaVisibility::Public)
                    ->count(),
            };
        }

        return $stats;
    }
}
