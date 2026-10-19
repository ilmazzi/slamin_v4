<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMyGroupsAction
{
    public function execute(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Group::query()
            ->with([
                'category.translations',
                'members' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->withCount([
                'activeMembers as active_members_count',
                'members as admins_count' => fn ($query) => $query
                    ->where('role', 'admin')
                    ->where('status', 'active'),
            ])
            ->whereHas('members', fn ($query) => $query
                ->where('user_id', $userId)
                ->whereIn('status', ['active', 'pending']))
            ->orderBy('name')
            ->paginate($perPage);
    }
}
