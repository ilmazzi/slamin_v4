<?php

namespace Domain\Group\Actions;

use Domain\Chat\Actions\SyncGroupChatMembersAction;
use Domain\Event\Models\Event;
use Domain\Group\Enums\GroupAffiliationStatus;
use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Enums\GroupContentType;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAffiliation;
use Domain\Group\Models\GroupContentLink;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Validation\ValidationException;

class AcceptGroupAffiliationAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly ForwardAcceptedEventsToParentsAction $forwardAcceptedEventsToParentsAction,
        private readonly SyncGroupChatMembersAction $syncGroupChatMembersAction,
    ) {}

    public function execute(Group $group, GroupAffiliation $affiliation, int $actorUserId): GroupAffiliation
    {
        $affiliation->loadMissing(['parent', 'child', 'initiatedBy']);

        if (! $affiliation->involves($group) || $affiliation->status !== GroupAffiliationStatus::Pending) {
            throw ValidationException::withMessages([
                'affiliation' => [__('group.errors.affiliation_not_pending')],
            ]);
        }

        if ((int) $affiliation->initiated_by_group_id === (int) $group->id) {
            throw ValidationException::withMessages([
                'affiliation' => [__('group.errors.affiliation_cannot_handle')],
            ]);
        }

        $affiliation->update([
            'status' => GroupAffiliationStatus::Accepted,
            'handled_by_user_id' => $actorUserId,
            'handled_at' => now(),
        ]);

        audit()->log(
            event: 'group_affiliation.accepted',
            userId: $actorUserId,
            auditable: $group,
            newValues: [
                'affiliation_id' => $affiliation->id,
                'parent_group_id' => $affiliation->parent_group_id,
                'child_group_id' => $affiliation->child_group_id,
            ],
            metadata: [
                'source' => 'group_affiliation_accept',
            ],
        );

        $eventIds = GroupContentLink::query()
            ->where('group_id', $affiliation->child_group_id)
            ->where('content_type', GroupContentType::Event)
            ->where('status', GroupContentLinkStatus::Accepted)
            ->pluck('content_id');

        $child = $affiliation->child;
        Event::query()->whereIn('id', $eventIds)->get()->each(function (Event $event) use ($child, $actorUserId) {
            $this->forwardAcceptedEventsToParentsAction->execute($event, $child, $actorUserId);
        });

        if ($affiliation->parent) {
            $this->syncGroupChatMembersAction->execute($affiliation->parent, false);
        }

        $initiator = $affiliation->initiatedBy;
        if ($initiator) {
            $this->notifyStaff($initiator, $group, $affiliation, $actorUserId);
        }

        return $affiliation->fresh(['parent', 'child', 'initiatedBy']);
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
                type: 'group_affiliation_accepted',
                title: fn () => __('group.notifications.affiliation_accepted_title'),
                message: fn () => __('group.notifications.affiliation_accepted_message', [
                    'group' => $from->name,
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
