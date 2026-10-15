<?php

namespace Domain\Moderation\Actions;

use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Validation\ValidationException;

class ApprovePoemAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Poem $poem, int $moderatorUserId, ?string $note = null): Poem
    {
        if ($poem->status !== PoemStatus::PendingReview) {
            throw ValidationException::withMessages([
                'poem' => [__('moderation.errors.poem_approve_pending_only')],
            ]);
        }

        $poem->update([
            'status' => PoemStatus::Published,
            'published_at' => $poem->published_at ?? now(),
        ]);

        $poem->moderationDecisions()->create([
            'moderator_user_id' => $moderatorUserId,
            'decision' => ModerationDecisionType::Approved,
            'note' => $note,
        ]);

        audit()->log(
            event: 'poem.approved',
            userId: $moderatorUserId,
            auditable: $poem,
            newValues: [
                'status' => $poem->status?->value,
                'published_at' => $poem->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'poem_moderation_approve',
                'note' => $note,
            ],
        );

        $this->createNotificationAction->execute(
            userId: $poem->user_id,
            type: 'poem_approved',
            title: fn () => __('moderation.notifications.poem_approved_title'),
            message: fn () => __('moderation.notifications.poem_approved_message', ['title' => $poem->title]),
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
