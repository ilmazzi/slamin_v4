<?php

namespace Domain\Group\Actions;

use Domain\Engagement\Models\Like;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Notification\Models\Notification;
use Domain\User\Models\User;

class LikeGroupAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Group $group, int $userId): void
    {
        $like = Like::query()->firstOrCreate([
            'user_id' => $userId,
            'likeable_type' => $group->getMorphClass(),
            'likeable_id' => $group->id,
        ]);

        $this->notifyAdmins($like, $group);

        audit()->log(
            event: 'group.liked',
            userId: $userId,
            auditable: $group,
            metadata: [
                'source' => 'group_like',
            ],
        );
    }

    private function notifyAdmins(Like $like, Group $group): void
    {
        if (! $like->wasRecentlyCreated) {
            return;
        }

        $adminIds = $group->members()
            ->where('status', GroupMemberStatus::Active)
            ->where('role', GroupMemberRole::Admin)
            ->where('user_id', '!=', $like->user_id)
            ->pluck('user_id');

        if ($adminIds->isEmpty()) {
            return;
        }

        $profile = User::query()->with('profile')->find($like->user_id)?->profile;
        $likerName = $profile?->display_name ?: $profile?->nickname;

        foreach ($adminIds as $adminId) {
            $alreadyNotified = Notification::query()
                ->where('user_id', $adminId)
                ->where('type', 'group_liked')
                ->where('data->liker_id', $like->user_id)
                ->where('data->content_id', $group->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $this->createNotificationAction->execute(
                userId: $adminId,
                type: 'group_liked',
                title: fn () => __('notifications.liked.title'),
                message: fn () => __('notifications.liked.message', [
                    'name' => $likerName ?? __('notifications.someone'),
                    'title' => $group->name,
                ]),
                data: [
                    'liker_id' => $like->user_id,
                    'liker_name' => $likerName,
                    'content_type' => 'group',
                    'content_id' => $group->id,
                    'content_title' => $group->name,
                    'group_slug' => $group->slug,
                ],
            );
        }
    }
}
