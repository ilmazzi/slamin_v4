<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\StoreTranslationCommentData;
use Domain\Gig\Enums\TranslationCommentStatus;
use Domain\Gig\Models\TranslationComment;
use Domain\Gig\Models\TranslationJob;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\Auth;

class StoreTranslationCommentAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(TranslationJob $job, StoreTranslationCommentData $data): TranslationComment
    {
        $comment = $job->comments()->create([
            'author_user_id' => Auth::id(),
            'comment_target' => $data->commentTarget,
            'target_locator' => $data->targetLocator,
            'content' => $data->content,
            'status' => TranslationCommentStatus::Open,
        ]);

        audit()->log(
            event: 'translation_comment.created',
            userId: Auth::id(),
            auditable: $comment,
            newValues: [
                'translation_job_id' => $job->id,
                'comment_target' => $comment->comment_target?->value,
                'target_locator' => $comment->target_locator,
            ],
            metadata: [
                'source' => 'translation_comment_store',
            ],
        );

        $targetUserId = (int) Auth::id() === (int) $job->owner_user_id ? $job->translator_user_id : $job->owner_user_id;
        $this->createNotificationAction->execute(
            userId: $targetUserId,
            type: 'translation_comment.created',
            title: fn () => __('gig.notifications.translation_comment_created_title'),
            message: fn () => __('gig.notifications.translation_comment_created_message'),
            data: [
                'translation_job_id' => $job->id,
                'comment_id' => $comment->id,
            ],
        );

        return $comment->fresh('author.profile');
    }
}
