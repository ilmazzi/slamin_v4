<?php

namespace Domain\Comment\Actions;

use Domain\Comment\Models\Comment;

class UpdateCommentAction
{
    public function execute(Comment $comment, int $userId, string $content): Comment
    {
        $oldValues = [
            'content' => $comment->content,
        ];

        $comment->update([
            'content' => $content,
        ]);

        audit()->log(
            event: 'comment.updated',
            userId: $userId,
            auditable: $comment,
            oldValues: $oldValues,
            newValues: [
                'content' => $comment->content,
            ],
            metadata: [
                'source' => 'comment_update',
            ],
        );

        return $comment->fresh(['user.profile', 'replies.user.profile']);
    }
}
