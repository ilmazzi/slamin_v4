<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupAffiliationStatus;
use Domain\Group\Models\Group;
use Illuminate\Support\Collection;

class ListGroupAffiliationsAction
{
    public function execute(Group $group): array
    {
        $relations = ['parent.category.translations', 'child.category.translations', 'initiatedBy'];

        $asParent = $group->childAffiliations()->with($relations)->latest()->get();
        $asChild = $group->parentAffiliations()->with($relations)->latest()->get();
        $all = $asParent->concat($asChild)->unique('id')->values();

        return [
            'parents' => $asChild->filter(fn ($item) => $item->status === GroupAffiliationStatus::Accepted)->values(),
            'children' => $asParent->filter(fn ($item) => $item->status === GroupAffiliationStatus::Accepted)->values(),
            'incoming' => $all
                ->filter(fn ($item) => $item->status === GroupAffiliationStatus::Pending
                    && (int) $item->initiated_by_group_id !== (int) $group->id)
                ->values(),
            'outgoing' => $all
                ->filter(fn ($item) => $item->status === GroupAffiliationStatus::Pending
                    && (int) $item->initiated_by_group_id === (int) $group->id)
                ->values(),
        ];
    }
}
