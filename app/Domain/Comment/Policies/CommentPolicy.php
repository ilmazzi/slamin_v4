<?php

namespace Domain\Comment\Policies;

use Domain\Comment\Models\Comment;
use Domain\Forum\Models\ForumPost;
use Domain\User\Models\User;

class CommentPolicy
{
    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->user_id === $user->id) {
            return true;
        }

        if ($user->isAdmin() || $user->isModerator()) {
            return $comment->commentable instanceof ForumPost;
        }

        return false;
    }
}
