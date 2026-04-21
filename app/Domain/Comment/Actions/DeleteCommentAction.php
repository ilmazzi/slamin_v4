<?php

namespace Domain\Comment\Actions;

use Domain\Comment\Models\Comment;

class DeleteCommentAction
{
    public function execute(Comment $comment, int $userId): void
    {
        $oldValues = [
            'commentable_type' => $comment->commentable_type,
            'commentable_id' => $comment->commentable_id,
            'parent_id' => $comment->parent_id,
            'content' => $comment->content,
        ];

        $comment->delete();

        audit()->log(
            event: 'comment.deleted',
            userId: $userId,
            auditable: $comment,
            oldValues: $oldValues,
            metadata: [
                'source' => 'comment_delete',
            ],
        );
    }
}
