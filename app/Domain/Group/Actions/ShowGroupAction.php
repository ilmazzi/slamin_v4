<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\Group\Models\Group;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowGroupAction
{
    public function execute(string $slug, ?int $authUserId = null): Group
    {
        $group = Group::query()
            ->with([
                'category.translations',
                'chatThreads',
                'members' => fn ($query) => $authUserId
                    ? $query->where('user_id', $authUserId)->with('user.profile', 'handledBy.profile')
                    : $query->whereRaw('1=0'),
            ])
            ->withCount([
                'activeMembers as active_members_count',
                'members as admins_count' => fn ($query) => $query
                    ->where('role', 'admin')
                    ->where('status', 'active'),
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if ($group->visibility === GroupVisibility::Public || $group->is_listed) {
            return $group;
        }

        if ($authUserId && $group->members->first()?->status === GroupMemberStatus::Active) {
            return $group;
        }

        throw new NotFoundHttpException(__('group.errors.not_visible'));
    }
}
