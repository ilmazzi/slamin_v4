<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\Group\Models\Group;
use Illuminate\Support\Collection;

class SearchProposeableGroupsAction
{
    public function execute(int $userId, string $query): Collection
    {
        $search = trim($query);

        return Group::query()
            ->with('category.translations')
            ->where('is_active', true)
            ->where(function ($groups) use ($userId) {
                $groups->where('visibility', GroupVisibility::Public)
                    ->orWhere('is_listed', true)
                    ->orWhereHas('members', function ($members) use ($userId) {
                        $members->where('user_id', $userId)
                            ->where('status', GroupMemberStatus::Active);
                    });
            })
            ->when($search !== '', fn ($groups) => $groups->search($search))
            ->orderBy('name')
            ->limit(10)
            ->get();
    }
}
