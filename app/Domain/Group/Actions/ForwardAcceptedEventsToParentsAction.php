<?php

namespace Domain\Group\Actions;

use Domain\Event\Models\Event;
use Domain\Group\Enums\GroupAffiliationStatus;
use Domain\Group\Enums\GroupContentLinkSource;
use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Enums\GroupContentType;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAffiliation;
use Domain\Group\Models\GroupContentLink;
use Domain\Notification\Actions\CreateNotificationAction;

class ForwardAcceptedEventsToParentsAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Event $event, Group $child, int $actorUserId, array $visited = []): void
    {
        if (in_array($child->id, $visited, true)) {
            return;
        }

        $visited[] = $child->id;

        $parentIds = GroupAffiliation::query()
            ->where('child_group_id', $child->id)
            ->where('status', GroupAffiliationStatus::Accepted)
            ->pluck('parent_group_id');

        foreach ($parentIds as $parentId) {
            $parent = Group::query()->whereKey($parentId)->where('is_active', true)->first();
            if (! $parent) {
                continue;
            }

            $link = GroupContentLink::query()
                ->where('group_id', $parent->id)
                ->where('content_type', GroupContentType::Event)
                ->where('content_id', $event->id)
                ->first();

            if ($link && in_array($link->status, [
                GroupContentLinkStatus::Pending,
                GroupContentLinkStatus::Accepted,
            ], true)) {
                continue;
            }

            $accepted = $parent->auto_accept_events;
            $payload = [
                'group_id' => $parent->id,
                'content_type' => GroupContentType::Event,
                'content_id' => $event->id,
                'status' => $accepted ? GroupContentLinkStatus::Accepted : GroupContentLinkStatus::Pending,
                'source' => GroupContentLinkSource::ChildForward,
                'source_group_id' => $child->id,
                'proposed_by_user_id' => $actorUserId,
                'handled_by_user_id' => $accepted ? $actorUserId : null,
                'handled_at' => $accepted ? now() : null,
            ];

            if ($link) {
                $link->update($payload);
            } else {
                $link = GroupContentLink::query()->create($payload);
            }

            audit()->log(
                event: $accepted ? 'group.content_accepted' : 'group.content_proposed',
                userId: $actorUserId,
                auditable: $parent,
                newValues: [
                    'link_id' => $link->id,
                    'content_type' => GroupContentType::Event->value,
                    'content_id' => $event->id,
                    'status' => $link->status?->value,
                    'source' => GroupContentLinkSource::ChildForward->value,
                    'source_group_id' => $child->id,
                ],
                metadata: [
                    'source' => 'group_child_forward',
                ],
            );

            if ($accepted) {
                $this->execute($event, $parent, $actorUserId, $visited);
                continue;
            }

            $this->notifyStaff($parent, $event, $child, $actorUserId);
        }
    }

    private function notifyStaff(Group $parent, Event $event, Group $child, int $actorUserId): void
    {
        $staffIds = $parent->members()
            ->where('status', GroupMemberStatus::Active)
            ->whereIn('role', [GroupMemberRole::Admin, GroupMemberRole::Editor])
            ->where('user_id', '!=', $actorUserId)
            ->pluck('user_id');

        foreach ($staffIds as $staffId) {
            $this->createNotificationAction->execute(
                userId: (int) $staffId,
                type: 'group_event_proposed',
                title: fn () => __('group.notifications.event_proposed_title'),
                message: fn () => __('group.notifications.event_forwarded_message', [
                    'event' => $event->title,
                    'child' => $child->name,
                    'group' => $parent->name,
                ]),
                data: [
                    'group_id' => $parent->id,
                    'group_slug' => $parent->slug,
                    'event_id' => $event->id,
                    'event_slug' => $event->slug,
                    'source_group_id' => $child->id,
                ],
            );
        }
    }
}
