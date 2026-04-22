<?php

namespace Domain\Group\Actions;

use Domain\Group\DTOs\UpdateGroupAnnouncementData;
use Domain\Group\Models\GroupAnnouncement;

class UpdateGroupAnnouncementAction
{
    public function execute(GroupAnnouncement $announcement, UpdateGroupAnnouncementData $data): GroupAnnouncement
    {
        $announcement->update([
            'title' => $data->title,
            'content' => $data->content,
            'is_pinned' => $data->isPinned,
        ]);

        audit()->log(
            event: 'group_announcement.updated',
            userId: $data->userId,
            auditable: $announcement->group,
            newValues: [
                'announcement_id' => $announcement->id,
                'title' => $announcement->title,
            ],
            metadata: [
                'source' => 'group_announcement_update',
            ],
        );

        return $announcement->fresh(['author.profile']);
    }
}
