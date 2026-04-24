<?php

namespace Domain\Comment\Policies;

use Domain\Comment\Models\Comment;
use Domain\User\Models\User;

class CommentPolicy
{
    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }
}
