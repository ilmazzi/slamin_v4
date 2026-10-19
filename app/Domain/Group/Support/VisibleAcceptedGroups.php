<?php

namespace Domain\Group\Support;

use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\Group\Models\GroupMember;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;

class VisibleAcceptedGroups
{
    public static function map($links, ?int $authUserId, FilesystemAdapter $disk): Collection
    {
        $accepted = collect($links)
            ->filter(fn ($link) => $link->status?->value === 'accepted' && $link->group);

        $unlistedIds = $accepted
            ->filter(fn ($link) => $link->group->visibility === GroupVisibility::Private && ! $link->group->is_listed)
            ->pluck('group_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $visibleUnlistedIds = $authUserId && $unlistedIds->isNotEmpty()
            ? GroupMember::query()
                ->where('user_id', $authUserId)
                ->where('status', GroupMemberStatus::Active)
                ->whereIn('group_id', $unlistedIds->all())
                ->pluck('group_id')
                ->map(fn ($id) => (int) $id)
            : collect();

        return $accepted
            ->filter(function ($link) use ($visibleUnlistedIds) {
                if ($link->group->visibility === GroupVisibility::Public || $link->group->is_listed) {
                    return true;
                }

                return $visibleUnlistedIds->contains((int) $link->group_id);
            })
            ->map(fn ($link) => [
                'id' => $link->group->id,
                'slug' => $link->group->slug,
                'name' => $link->group->name,
                'image_url' => $link->group->image_path ? $disk->url($link->group->image_path) : null,
                'visibility' => $link->group->visibility?->value,
                'is_listed' => (bool) $link->group->is_listed,
                'category' => $link->group->category ? [
                    'id' => $link->group->category->id,
                    'name' => $link->group->category->translatedName(),
                ] : null,
            ])
            ->values();
    }
}
