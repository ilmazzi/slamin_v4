<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupAffiliationStatus;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAffiliation;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Validation\ValidationException;

class RejectGroupAffiliationAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
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
            'status' => GroupAffiliationStatus::Rejected,
            'handled_by_user_id' => $actorUserId,
            'handled_at' => now(),
        ]);

        audit()->log(
            event: 'group_affiliation.rejected',
            userId: $actorUserId,
            auditable: $group,
            newValues: [
                'affiliation_id' => $affiliation->id,
            ],
            metadata: [
                'source' => 'group_affiliation_reject',
            ],
        );

        $initiator = $affiliation->initiatedBy;
        if ($initiator) {
            $staffIds = $initiator->members()
                ->where('status', GroupMemberStatus::Active)
                ->whereIn('role', [GroupMemberRole::Admin, GroupMemberRole::Editor])
                ->where('user_id', '!=', $actorUserId)
                ->pluck('user_id');

            foreach ($staffIds as $staffId) {
                $this->createNotificationAction->execute(
                    userId: (int) $staffId,
                    type: 'group_affiliation_rejected',
                    title: fn () => __('group.notifications.affiliation_rejected_title'),
                    message: fn () => __('group.notifications.affiliation_rejected_message', [
                        'group' => $group->name,
                    ]),
                    data: [
                        'group_id' => $initiator->id,
                        'group_slug' => $initiator->slug,
                        'affiliation_id' => $affiliation->id,
                    ],
                );
            }
        }

        return $affiliation->fresh(['parent', 'child', 'initiatedBy']);
    }
}
