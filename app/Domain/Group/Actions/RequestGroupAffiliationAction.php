<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupAffiliationStatus;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAffiliation;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Validation\ValidationException;

class RequestGroupAffiliationAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Group $actorGroup, int $actorUserId, ?int $parentGroupId, ?int $childGroupId): GroupAffiliation
    {
        $hasParent = filled($parentGroupId);
        $hasChild = filled($childGroupId);

        if ($hasParent === $hasChild) {
            throw ValidationException::withMessages([
                'group' => [__('group.errors.affiliation_invalid')],
            ]);
        }

        if ($hasParent) {
            $child = $actorGroup;
            $parent = $this->findTarget($parentGroupId, $actorUserId);
        } else {
            $parent = $actorGroup;
            $child = $this->findTarget($childGroupId, $actorUserId);
        }

        if ((int) $parent->id === (int) $child->id) {
            throw ValidationException::withMessages([
                'group' => [__('group.errors.affiliation_self')],
            ]);
        }

        $existing = GroupAffiliation::query()
            ->where('parent_group_id', $parent->id)
            ->where('child_group_id', $child->id)
            ->first();

        if ($existing && in_array($existing->status, [
            GroupAffiliationStatus::Pending,
            GroupAffiliationStatus::Accepted,
        ], true)) {
            throw ValidationException::withMessages([
                'group' => [__('group.errors.affiliation_exists')],
            ]);
        }

        $reverse = GroupAffiliation::query()
            ->where('parent_group_id', $child->id)
            ->where('child_group_id', $parent->id)
            ->whereIn('status', [
                GroupAffiliationStatus::Pending->value,
                GroupAffiliationStatus::Accepted->value,
            ])
            ->exists();

        if ($reverse || $this->wouldCreateCycle($parent, $child)) {
            throw ValidationException::withMessages([
                'group' => [__('group.errors.affiliation_cycle')],
            ]);
        }

        $payload = [
            'parent_group_id' => $parent->id,
            'child_group_id' => $child->id,
            'status' => GroupAffiliationStatus::Pending,
            'initiated_by_group_id' => $actorGroup->id,
            'handled_by_user_id' => null,
            'handled_at' => null,
        ];

        if ($existing) {
            $existing->update($payload);
            $affiliation = $existing->fresh(['parent', 'child', 'initiatedBy']);
        } else {
            $affiliation = GroupAffiliation::query()->create($payload)->load(['parent', 'child', 'initiatedBy']);
        }

        audit()->log(
            event: 'group_affiliation.requested',
            userId: $actorUserId,
            auditable: $actorGroup,
            newValues: [
                'affiliation_id' => $affiliation->id,
                'parent_group_id' => $parent->id,
                'child_group_id' => $child->id,
            ],
            metadata: [
                'source' => 'group_affiliation_request',
            ],
        );

        $counterpart = $affiliation->counterpart($actorGroup);
        if ($counterpart) {
            $this->notifyStaff($counterpart, $actorGroup, $affiliation, $actorUserId);
        }

        return $affiliation;
    }

    private function findTarget(?int $groupId, int $userId): Group
    {
        $group = Group::query()->whereKey($groupId)->where('is_active', true)->first();
        if (! $group || ! $group->canReceiveProposalFrom($userId)) {
            throw ValidationException::withMessages([
                'group' => [__('group.errors.affiliation_invalid')],
            ]);
        }

        return $group;
    }

    private function wouldCreateCycle(Group $parent, Group $child): bool
    {
        $currentId = $parent->id;
        $guard = 0;

        while ($currentId && $guard < 20) {
            if ((int) $currentId === (int) $child->id) {
                return true;
            }

            $currentId = GroupAffiliation::query()
                ->where('child_group_id', $currentId)
                ->where('status', GroupAffiliationStatus::Accepted)
                ->value('parent_group_id');
            $guard++;
        }

        return false;
    }

    private function notifyStaff(Group $target, Group $from, GroupAffiliation $affiliation, int $actorUserId): void
    {
        $staffIds = $target->members()
            ->where('status', GroupMemberStatus::Active)
            ->whereIn('role', [GroupMemberRole::Admin, GroupMemberRole::Editor])
            ->where('user_id', '!=', $actorUserId)
            ->pluck('user_id');

        foreach ($staffIds as $staffId) {
            $this->createNotificationAction->execute(
                userId: (int) $staffId,
                type: 'group_affiliation_requested',
                title: fn () => __('group.notifications.affiliation_requested_title'),
                message: fn () => __('group.notifications.affiliation_requested_message', [
                    'from' => $from->name,
                    'group' => $target->name,
                ]),
                data: [
                    'group_id' => $target->id,
                    'group_slug' => $target->slug,
                    'affiliation_id' => $affiliation->id,
                ],
            );
        }
    }
}
