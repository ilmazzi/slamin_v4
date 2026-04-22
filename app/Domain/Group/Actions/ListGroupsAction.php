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
                'members' => fn ($query) => $authUserId
                    ? $query->where('user_id', $authUserId)
                    : $query->whereRaw('1=0'),
            ])
            ->withCount([
                'activeMembers as active_members_count',
                'members as owners_count' => fn ($query) => $query
                    ->where('role', 'owner')
                    ->where('status', 'active'),
            ])
            ->visibleToPublic()
            ->orderBy('name');

        if ($data->query) {
            $search = $data->query;
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($data->perPage);
    }
}
