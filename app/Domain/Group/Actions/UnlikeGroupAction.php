<?php

namespace Domain\Group\Actions;

use Domain\Engagement\Models\Like;
use Domain\Group\Models\Group;

class UnlikeGroupAction
{
    public function execute(Group $group, int $userId): void
    {
        Like::query()
            ->where('user_id', $userId)
            ->where('likeable_type', $group->getMorphClass())
            ->where('likeable_id', $group->id)
            ->delete();

        audit()->log(
            event: 'group.unliked',
            userId: $userId,
            auditable: $group,
            metadata: [
                'source' => 'group_unlike',
            ],
        );
    }
}
