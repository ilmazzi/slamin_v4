<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\GroupAnnouncement;

class DeleteGroupAnnouncementAction
{
    public function execute(GroupAnnouncement $announcement, int $userId): void
    {
        audit()->log(
            event: 'group_announcement.deleted',
            userId: $userId,
            auditable: $announcement->group,
            metadata: [
                'source' => 'group_announcement_delete',
                'announcement_id' => $announcement->id,
            ],
        );

        $announcement->delete();
    }
}
