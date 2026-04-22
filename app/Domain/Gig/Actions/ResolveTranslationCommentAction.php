<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\TranslationCommentStatus;
use Domain\Gig\Models\TranslationComment;
use Illuminate\Support\Facades\Auth;

class ResolveTranslationCommentAction
{
    public function execute(TranslationComment $comment): TranslationComment
    {
        $comment->status = TranslationCommentStatus::Resolved;
        $comment->resolved_at = now();
        $comment->save();

        audit()->log(
            event: 'translation_comment.resolved',
            userId: Auth::id(),
            auditable: $comment,
            newValues: [
                'status' => $comment->status?->value,
                'resolved_at' => $comment->resolved_at?->toISOString(),
            ],
            metadata: ['source' => 'translation_comment_resolve'],
        );

        return $comment->fresh('author.profile');
    }
}
