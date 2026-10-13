<?php

namespace Domain\Comment\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListCommentsAction
{
    public function execute(Model $commentable, int $perPage = 10): LengthAwarePaginator
    {
        $this->ensureCommentableIsVisible($commentable);

        return Comment::query()
            ->with([
                'user.profile',
                'replies.user.profile',
            ])
            ->withCount('replies')
            ->where('commentable_type', $commentable->getMorphClass())
            ->where('commentable_id', $commentable->getKey())
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    private function ensureCommentableIsVisible(Model $commentable): void
    {
        if ($commentable instanceof Poem) {
            if ($commentable->status === PoemStatus::Published && $commentable->is_public === true) {
                return;
            }
        }

        if ($commentable instanceof Article) {
            if ($commentable->status === ArticleStatus::Published && $commentable->is_public === true) {
                return;
            }
        }

        if ($commentable instanceof Event) {
            if ($commentable->status === EventStatus::Published && $commentable->is_public === true) {
                return;
            }
        }

        if ($commentable instanceof MediaItem) {
            if ($commentable->status === MediaStatus::Ready && $commentable->visibility === MediaVisibility::Public) {
                return;
            }
        }

        throw new NotFoundHttpException;
    }
}
