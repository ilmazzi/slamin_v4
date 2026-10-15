<?php

namespace Domain\Moderation\Actions;

use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Validation\ValidationException;

class RejectPoemAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Poem $poem, int $moderatorUserId, string $note): Poem
    {
        if ($poem->status !== PoemStatus::PendingReview) {
            throw ValidationException::withMessages([
                'poem' => [__('moderation.errors.poem_reject_pending_only')],
            ]);
        }

        $poem->update([
            'status' => PoemStatus::Rejected,
            'published_at' => null,
        ]);

        $poem->moderationDecisions()->create([
            'moderator_user_id' => $moderatorUserId,
            'decision' => ModerationDecisionType::Rejected,
            'note' => $note,
        ]);

        audit()->log(
            event: 'poem.rejected',
            userId: $moderatorUserId,
            auditable: $poem,
            newValues: [
                'status' => $poem->status?->value,
                'published_at' => $poem->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'poem_moderation_reject',
                'note' => $note,
            ],
        );

        $this->createNotificationAction->execute(
            userId: $poem->user_id,
            type: 'poem_rejected',
            title: fn () => __('moderation.notifications.poem_rejected_title'),
            message: fn () => __('moderation.notifications.poem_rejected_message', ['title' => $poem->title]),
            data: [
                'poem_id' => $poem->id,
                'poem_slug' => $poem->slug,
                'status' => $poem->status?->value,
                'moderation_note' => $note,
            ],
        );

        return $poem->fresh(['user.profile', 'category.translations', 'type.translations']);
    }
}
