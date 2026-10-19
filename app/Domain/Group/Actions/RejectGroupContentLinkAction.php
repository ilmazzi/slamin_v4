<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Enums\GroupContentType;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupContentLink;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Validation\ValidationException;

class RejectGroupContentLinkAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Group $group, GroupContentLink $link, int $actorUserId): GroupContentLink
    {
        match ($link->content_type) {
            GroupContentType::Poem => $link->loadMissing('poem'),
            GroupContentType::Article => $link->loadMissing('article'),
            default => $link->loadMissing('event'),
        };

        if ($link->group_id !== $group->id || $link->status !== GroupContentLinkStatus::Pending) {
            throw ValidationException::withMessages([
                'link' => [__('group.errors.content_not_pending')],
            ]);
        }

        $link->update([
            'status' => GroupContentLinkStatus::Rejected,
            'handled_by_user_id' => $actorUserId,
            'handled_at' => now(),
        ]);

        audit()->log(
            event: 'group.content_rejected',
            userId: $actorUserId,
            auditable: $group,
            newValues: [
                'link_id' => $link->id,
                'content_type' => $link->content_type?->value,
                'content_id' => $link->content_id,
            ],
            metadata: [
                'source' => 'group_content_reject',
            ],
        );

        if ($link->proposed_by_user_id && $link->proposed_by_user_id !== $actorUserId) {
            $kind = $link->content_type?->value ?? 'event';
            $title = match ($link->content_type) {
                GroupContentType::Poem => $link->poem?->title ?? '',
                GroupContentType::Article => $link->article?->title ?? '',
                default => $link->event?->title ?? '',
            };
            $slug = match ($link->content_type) {
                GroupContentType::Poem => $link->poem?->slug,
                GroupContentType::Article => $link->article?->slug,
                default => $link->event?->slug,
            };

            $this->createNotificationAction->execute(
                userId: (int) $link->proposed_by_user_id,
                type: "group_{$kind}_rejected",
                title: fn () => __("group.notifications.{$kind}_rejected_title"),
                message: fn () => __("group.notifications.{$kind}_rejected_message", [
                    'event' => $title,
                    'title' => $title,
                    'group' => $group->name,
                ]),
                data: [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                    "{$kind}_id" => $link->content_id,
                    "{$kind}_slug" => $slug,
                ],
            );
        }

        return $link->fresh(['event', 'poem', 'article', 'proposedBy.profile']);
    }
}
