<?php

namespace App\Policies;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\User\Models\User;

class ArticlePolicy
{
    public function update(User $user, Article $article): bool
    {
        return $article->user_id === $user->id;
    }

    public function delete(User $user, Article $article): bool
    {
        return $article->user_id === $user->id;
    }

    public function like(User $user, Article $article): bool
    {
        return $this->canInteract($user, $article);
    }

    public function bookmark(User $user, Article $article): bool
    {
        return $this->canInteract($user, $article);
    }

    private function canInteract(User $user, Article $article): bool
    {
        if ($article->user_id === $user->id) {
            return true;
        }

        return $article->status === ArticleStatus::Published
            && $article->is_public === true;
    }
}
