<?php

namespace Domain\Group\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Enums\GroupContentType;
use Domain\Group\Models\Group;
use Domain\Poetry\Enums\PoemStatus;

class ListGroupPublicationsAction
{
    public function execute(Group $group, bool $canViewPrivate = false): array
    {
        $links = $group->contentLinks()
            ->where('status', GroupContentLinkStatus::Accepted)
            ->whereIn('content_type', [
                GroupContentType::Poem->value,
                GroupContentType::Article->value,
            ])
            ->orderByDesc('created_at')
            ->get();

        $links->filter(fn ($link) => $link->content_type === GroupContentType::Poem)->load('poem.user.profile');
        $links->filter(fn ($link) => $link->content_type === GroupContentType::Article)->load('article.user.profile');

        $poems = $links
            ->filter(fn ($link) => $link->content_type === GroupContentType::Poem && $link->poem)
            ->map(fn ($link) => $link->poem)
            ->filter(function ($poem) use ($canViewPrivate) {
                if ($canViewPrivate) {
                    return true;
                }

                return $poem->status === PoemStatus::Published && $poem->is_public;
            })
            ->values();

        $articles = $links
            ->filter(fn ($link) => $link->content_type === GroupContentType::Article && $link->article)
            ->map(fn ($link) => $link->article)
            ->filter(function ($article) use ($canViewPrivate) {
                if ($canViewPrivate) {
                    return true;
                }

                return $article->status === ArticleStatus::Published && $article->is_public;
            })
            ->values();

        return [
            'poems' => $poems,
            'articles' => $articles,
        ];
    }
}
