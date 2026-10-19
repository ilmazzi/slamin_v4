<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupContentLinkSource;
use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Enums\GroupContentType;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupContentLink;
use Domain\Notification\Actions\CreateNotificationAction;

class ProposeContentToGroupsAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(
        GroupContentType $type,
        int $contentId,
        string $title,
        ?string $slug,
        array $groupIds,
        int $userId,
    ): void {
        $groupIds = collect($groupIds)
            ->filter(fn ($groupId): bool => is_numeric($groupId))
            ->map(fn ($groupId): int => (int) $groupId)
            ->unique()
            ->values();

        $existing = GroupContentLink::query()
            ->where('content_type', $type)
            ->where('content_id', $contentId)
            ->get()
            ->keyBy('group_id');

        foreach ($groupIds as $groupId) {
            $group = Group::query()->whereKey($groupId)->where('is_active', true)->first();
            if (! $group) {
                continue;
            }

            $link = $existing->get($groupId);
            if ($link && in_array($link->status, [
                GroupContentLinkStatus::Pending,
                GroupContentLinkStatus::Accepted,
            ], true)) {
                continue;
            }

            $isStaff = $group->hasActiveRole($userId, [
                GroupMemberRole::Admin,
                GroupMemberRole::Editor,
            ]);
            $status = $isStaff
                ? GroupContentLinkStatus::Accepted
                : GroupContentLinkStatus::Pending;
            $source = $isStaff
                ? GroupContentLinkSource::Staff
                : GroupContentLinkSource::Direct;

            $payload = [
                'group_id' => $group->id,
                'content_type' => $type,
                'content_id' => $contentId,
                'status' => $status,
                'source' => $source,
                'proposed_by_user_id' => $userId,
                'handled_by_user_id' => $isStaff ? $userId : null,
                'handled_at' => $isStaff ? now() : null,
            ];

            if ($link) {
                $link->update($payload);
            } else {
                $link = GroupContentLink::query()->create($payload);
            }

            audit()->log(
                event: $isStaff ? 'group.content_accepted' : 'group.content_proposed',
                userId: $userId,
                auditable: $group,
                newValues: [
                    'link_id' => $link->id,
                    'content_type' => $type->value,
                    'content_id' => $contentId,
                    'status' => $status->value,
                ],
                metadata: [
                    'source' => "{$type->value}_group_propose",
                ],
            );

            if ($status === GroupContentLinkStatus::Pending) {
                $this->notifyStaff($group, $type, $title, $slug, $contentId, $userId);
            }
        }

        foreach ($existing as $groupId => $link) {
            if (
                $groupIds->contains((int) $groupId)
                || $link->status === GroupContentLinkStatus::Removed
            ) {
                continue;
            }

            $link->update([
                'status' => GroupContentLinkStatus::Removed,
                'handled_by_user_id' => $userId,
                'handled_at' => now(),
            ]);
        }
    }

    private function notifyStaff(
        Group $group,
        GroupContentType $type,
        string $title,
        ?string $slug,
        int $contentId,
        int $actorUserId,
    ): void {
        $staffIds = $group->members()
            ->where('status', GroupMemberStatus::Active)
            ->whereIn('role', [GroupMemberRole::Admin, GroupMemberRole::Editor])
            ->where('user_id', '!=', $actorUserId)
            ->pluck('user_id');

        $kind = $type->value;

        foreach ($staffIds as $staffId) {
            $this->createNotificationAction->execute(
                userId: (int) $staffId,
                type: "group_{$kind}_proposed",
                title: fn () => __("group.notifications.{$kind}_proposed_title"),
                message: fn () => __("group.notifications.{$kind}_proposed_message", [
                    'title' => $title,
                    'group' => $group->name,
                ]),
                data: [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                    "{$kind}_id" => $contentId,
                    "{$kind}_slug" => $slug,
                ],
            );
        }
    }
}
