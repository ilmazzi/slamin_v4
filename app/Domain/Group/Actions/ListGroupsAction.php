<?php

namespace Domain\Group\Actions;

use Domain\Group\DTOs\ListGroupsData;
use Domain\Group\Models\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListGroupsAction
{
    public function execute(ListGroupsData $data, ?int $authUserId = null): LengthAwarePaginator
    {
        $query = Group::query()
            ->with([
                'category.translations',
                'members' => fn ($query) => $authUserId
                    ? $query->where('user_id', $authUserId)
                    : $query->whereRaw('1=0'),
            ])
            ->withCount([
                'activeMembers as active_members_count',
                'members as admins_count' => fn ($query) => $query
                    ->where('role', 'admin')
                    ->where('status', 'active'),
            ])
            ->listed()
            ->orderBy('name');

        if ($data->query) {
            $query->search($data->query);
        }

        return $query->paginate($data->perPage);
    }
}
