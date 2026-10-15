<?php

namespace Domain\Moderation\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Validation\ValidationException;

class ApproveArticleAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Article $article, int $moderatorUserId, ?string $note = null): Article
    {
        if ($article->status !== ArticleStatus::PendingReview) {
            throw ValidationException::withMessages([
                'article' => [__('moderation.errors.article_approve_pending_only')],
            ]);
        }

        $article->update([
            'status' => ArticleStatus::Published,
            'published_at' => $article->published_at ?? now(),
        ]);

        $article->moderationDecisions()->create([
            'moderator_user_id' => $moderatorUserId,
            'decision' => ModerationDecisionType::Approved,
            'note' => $note,
        ]);

        audit()->log(
            event: 'article.approved',
            userId: $moderatorUserId,
            auditable: $article,
            newValues: [
                'status' => $article->status?->value,
                'published_at' => $article->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'article_moderation_approve',
                'note' => $note,
            ],
        );

        $this->createNotificationAction->execute(
            userId: $article->user_id,
            type: 'article_approved',
            title: fn () => __('moderation.notifications.article_approved_title'),
            message: fn () => __('moderation.notifications.article_approved_message', ['title' => $article->title]),
            data: [
                'article_id' => $article->id,
                'article_slug' => $article->slug,
                'status' => $article->status?->value,
                'moderation_note' => $note,
            ],
        );

        return $article->fresh(['user.profile', 'category.translations']);
    }
}
