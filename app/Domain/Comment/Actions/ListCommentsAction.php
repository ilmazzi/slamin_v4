<?php

namespace Domain\Comment\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Forum\Models\ForumPost;
use Domain\Forum\Policies\ForumPostPolicy;
use Domain\Group\Models\Group;
use Domain\User\Models\User;
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
    public function execute(Model $commentable, int $perPage = 10, ?int $viewerId = null): LengthAwarePaginator
    {
        $this->ensureCommentableIsVisible($commentable, $viewerId);

        $query = Comment::query()
            ->with([
                'user.profile',
                'replies.user.profile',
            ])
            ->withCount('replies')
            ->where('commentable_type', $commentable->getMorphClass())
            ->where('commentable_id', $commentable->getKey())
            ->whereNull('parent_id');

        if ($commentable instanceof ForumPost) {
            if ($viewerId) {
                $query->with([
                    'votes' => fn ($votes) => $votes->where('user_id', $viewerId),
                    'replies.votes' => fn ($votes) => $votes->where('user_id', $viewerId),
                ]);
            }

            $query->orderByDesc('score')->orderByDesc('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage);
    }

    private function ensureCommentableIsVisible(Model $commentable, ?int $viewerId = null): void
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

        if ($commentable instanceof Group) {
            if ($commentable->is_active) {
                return;
            }
        }

        if ($commentable instanceof MediaItem) {
            if ($commentable->status === MediaStatus::Ready && $commentable->visibility === MediaVisibility::Public) {
                return;
            }
        }

        if ($commentable instanceof ForumPost) {
            $commentable->loadMissing('category');
            $viewer = $viewerId ? User::query()->find($viewerId) : null;

            if ((new ForumPostPolicy)->view($viewer, $commentable)) {
                return;
            }
        }

        throw new NotFoundHttpException;
    }
}
