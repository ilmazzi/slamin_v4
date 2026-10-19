<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupAffiliationStatus;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAffiliation;
use Illuminate\Support\Collection;

class SearchAffiliableGroupsAction
{
    public function execute(Group $group, int $userId, string $query): Collection
    {
        $search = trim($query);
        $relatedIds = GroupAffiliation::query()
            ->where(function ($affiliations) use ($group) {
                $affiliations->where('parent_group_id', $group->id)
                    ->orWhere('child_group_id', $group->id);
            })
            ->whereIn('status', [
                GroupAffiliationStatus::Pending->value,
                GroupAffiliationStatus::Accepted->value,
            ])
            ->get()
            ->flatMap(fn (GroupAffiliation $item) => [$item->parent_group_id, $item->child_group_id])
            ->unique()
            ->values();

        return Group::query()
            ->with('category.translations')
            ->where('is_active', true)
            ->whereKeyNot($group->id)
            ->when($relatedIds->isNotEmpty(), fn ($groups) => $groups->whereNotIn('id', $relatedIds->all()))
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
