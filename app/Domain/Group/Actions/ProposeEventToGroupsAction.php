<?php

namespace Domain\Group\Actions;

use Domain\Event\Models\Event;
use Domain\Group\Enums\GroupContentLinkSource;
use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Enums\GroupContentType;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupContentLink;
use Domain\Notification\Actions\CreateNotificationAction;

class ProposeEventToGroupsAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly ForwardAcceptedEventsToParentsAction $forwardAcceptedEventsToParentsAction,
    ) {}

    public function execute(Event $event, array $groupIds, int $userId): void
    {
        $groupIds = collect($groupIds)
            ->filter(fn ($groupId): bool => is_numeric($groupId))
            ->map(fn ($groupId): int => (int) $groupId)
            ->unique()
            ->values();

        $existing = GroupContentLink::query()
            ->where('content_type', GroupContentType::Event)
            ->where('content_id', $event->id)
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
            $accepted = $isStaff || $group->auto_accept_events;
            $status = $accepted
                ? GroupContentLinkStatus::Accepted
                : GroupContentLinkStatus::Pending;
            $source = $isStaff
                ? GroupContentLinkSource::Staff
                : GroupContentLinkSource::Direct;

            $payload = [
                'group_id' => $group->id,
                'content_type' => GroupContentType::Event,
                'content_id' => $event->id,
                'status' => $status,
                'source' => $source,
                'proposed_by_user_id' => $userId,
                'handled_by_user_id' => $accepted ? $userId : null,
                'handled_at' => $accepted ? now() : null,
            ];

            if ($link) {
                $link->update($payload);
            } else {
                $link = GroupContentLink::query()->create($payload);
            }

            audit()->log(
                event: $accepted ? 'group.content_accepted' : 'group.content_proposed',
                userId: $userId,
                auditable: $group,
                newValues: [
                    'link_id' => $link->id,
                    'content_type' => GroupContentType::Event->value,
                    'content_id' => $event->id,
                    'status' => $status->value,
                ],
                metadata: [
                    'source' => 'event_group_propose',
                ],
            );

            if ($status === GroupContentLinkStatus::Pending) {
                $this->notifyStaff($group, $event, $userId);
            }

            if ($status === GroupContentLinkStatus::Accepted) {
                $this->forwardAcceptedEventsToParentsAction->execute($event, $group, $userId);
            }
        }

        foreach ($existing as $groupId => $link) {
            if (
                $groupIds->contains((int) $groupId)
                || $link->status === GroupContentLinkStatus::Removed
                || $link->source === GroupContentLinkSource::ChildForward
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

    private function notifyStaff(Group $group, Event $event, int $actorUserId): void
    {
        $staffIds = $group->members()
            ->where('status', GroupMemberStatus::Active)
            ->whereIn('role', [GroupMemberRole::Admin, GroupMemberRole::Editor])
            ->where('user_id', '!=', $actorUserId)
            ->pluck('user_id');

        foreach ($staffIds as $staffId) {
            $this->createNotificationAction->execute(
                userId: (int) $staffId,
                type: 'group_event_proposed',
                title: fn () => __('group.notifications.event_proposed_title'),
                message: fn () => __('group.notifications.event_proposed_message', [
                    'event' => $event->title,
                    'group' => $group->name,
                ]),
                data: [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                    'event_id' => $event->id,
                    'event_slug' => $event->slug,
                ],
            );
        }
    }
}
