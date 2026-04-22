<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\UpdateTranslationCommentData;
use Domain\Gig\Models\TranslationComment;
use Illuminate\Support\Facades\Auth;

class UpdateTranslationCommentAction
{
    public function execute(TranslationComment $comment, UpdateTranslationCommentData $data): TranslationComment
    {
        $oldContent = $comment->content;

        if ($data->contentProvided) {
            $comment->content = $data->content;
        }

        $comment->save();

        audit()->log(
            event: 'translation_comment.updated',
            userId: Auth::id(),
            auditable: $comment,
            oldValues: ['content' => $oldContent],
            newValues: ['content' => $comment->content],
            metadata: ['source' => 'translation_comment_update'],
        );

        return $comment->fresh('author.profile');
    }
}
