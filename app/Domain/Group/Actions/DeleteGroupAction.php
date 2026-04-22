<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\Group;

class DeleteGroupAction
{
    public function execute(Group $group, int $userId): void
    {
        $group->delete();

        audit()->log(
            event: 'group.deleted',
            userId: $userId,
            auditable: $group,
            metadata: [
                'source' => 'group_delete',
            ],
        );
    }
}
