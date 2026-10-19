<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupContentLink;
use Illuminate\Validation\ValidationException;

class RemoveGroupContentLinkAction
{
    public function execute(Group $group, GroupContentLink $link, int $actorUserId): GroupContentLink
    {
        if ($link->group_id !== $group->id || $link->status !== GroupContentLinkStatus::Accepted) {
            throw ValidationException::withMessages([
                'link' => [__('group.errors.content_not_accepted')],
            ]);
        }

        $link->update([
            'status' => GroupContentLinkStatus::Removed,
            'handled_by_user_id' => $actorUserId,
            'handled_at' => now(),
        ]);

        audit()->log(
            event: 'group.content_removed',
            userId: $actorUserId,
            auditable: $group,
            newValues: [
                'link_id' => $link->id,
                'content_type' => $link->content_type?->value,
                'content_id' => $link->content_id,
            ],
            metadata: [
                'source' => 'group_content_remove',
            ],
        );

        return $link->fresh(['event', 'proposedBy.profile']);
    }
}
