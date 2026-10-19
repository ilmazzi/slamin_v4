<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Enums\GroupContentType;
use Domain\Group\Models\Group;
use Illuminate\Database\Eloquent\Collection;

class ListGroupContentLinksAction
{
    public function execute(Group $group, ?GroupContentLinkStatus $status = null): Collection
    {
        $links = $group->contentLinks()
            ->with(['proposedBy.profile', 'sourceGroup'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->whereIn('content_type', [
                GroupContentType::Event,
                GroupContentType::Poem,
                GroupContentType::Article,
            ])
            ->orderByDesc('created_at')
            ->get();

        $links->filter(fn ($link) => $link->content_type === GroupContentType::Event)->load('event.occurrences');
        $links->filter(fn ($link) => $link->content_type === GroupContentType::Poem)->load('poem');
        $links->filter(fn ($link) => $link->content_type === GroupContentType::Article)->load('article');

        return $links;
    }
}
