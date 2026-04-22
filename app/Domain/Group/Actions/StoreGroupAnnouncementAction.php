<?php

namespace Domain\Group\Actions;

use Domain\Group\DTOs\StoreGroupAnnouncementData;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAnnouncement;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\DB;

class StoreGroupAnnouncementAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(Group $group, StoreGroupAnnouncementData $data): GroupAnnouncement
    {
        return DB::transaction(function () use ($group, $data) {
            $announcement = $group->announcements()->create([
                'author_user_id' => $data->userId,
                'title' => $data->title,
                'content' => $data->content,
                'is_pinned' => $data->isPinned,
                'published_at' => now(),
            ]);

            audit()->log(
                event: 'group_announcement.created',
                userId: $data->userId,
                auditable: $group,
                newValues: [
                    'announcement_id' => $announcement->id,
                    'title' => $announcement->title,
                ],
                metadata: [
                    'source' => 'group_announcement_store',
                ],
            );

            $memberIds = $group->members()
                ->where('status', 'active')
                ->where('user_id', '!=', $data->userId)
                ->pluck('user_id');

            foreach ($memberIds as $memberId) {
                $this->createNotificationAction->execute(
                    userId: (int) $memberId,
                    type: 'group_announcement_published',
                    title: __('group.notifications.announcement_published_title'),
                    message: __('group.notifications.announcement_published_message', [
                        'group' => $group->name,
                        'title' => $announcement->title,
                    ]),
                    data: [
                        'group_id' => $group->id,
                        'group_slug' => $group->slug,
                        'announcement_id' => $announcement->id,
                    ],
                );
            }

            return $announcement->load('author.profile');
        });
    }
}
