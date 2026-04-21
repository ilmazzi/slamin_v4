<?php

namespace Domain\Comment\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreCommentAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(Model $commentable, int $userId, string $content, ?int $parentId = null): Comment
    {
        $this->ensureCommentableIsVisible($commentable);

        return DB::transaction(function () use ($commentable, $userId, $content, $parentId) {
            $parent = null;

            if ($parentId !== null) {
                $parent = Comment::query()->findOrFail($parentId);

                if ($parent->parent_id !== null) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['Only one reply level is allowed.'],
                    ]);
                }

                if (
                    $parent->commentable_type !== $commentable->getMorphClass()
                    || (int) $parent->commentable_id !== (int) $commentable->getKey()
                ) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['The parent comment does not belong to this content.'],
                    ]);
                }
            }

            $comment = Comment::query()->create([
                'user_id' => $userId,
                'commentable_type' => $commentable->getMorphClass(),
                'commentable_id' => $commentable->getKey(),
                'parent_id' => $parent?->id,
                'content' => $content,
            ]);

            audit()->log(
                event: 'comment.created',
                userId: $userId,
                auditable: $comment,
                newValues: [
                    'commentable_type' => $comment->commentable_type,
                    'commentable_id' => $comment->commentable_id,
                    'parent_id' => $comment->parent_id,
                    'content' => $comment->content,
                ],
                metadata: [
                    'source' => 'comment_store',
                ],
            );

            $this->notifyInterestedUsers($commentable, $comment, $parent);

            return $comment->load('user.profile');
        });
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

        throw new NotFoundHttpException();
    }

    private function notifyInterestedUsers(Model $commentable, Comment $comment, ?Comment $parent): void
    {
        $commentableType = $commentable instanceof Poem ? 'poem' : 'article';
        $slug = $commentable->slug ?? null;

        if ($commentable->user_id !== $comment->user_id) {
            $this->createNotificationAction->execute(
                userId: $commentable->user_id,
                type: "{$commentableType}_commented",
                title: $commentableType === 'poem' ? 'Nuovo commento sulla tua poesia' : 'Nuovo commento sul tuo articolo',
                message: $commentableType === 'poem'
                    ? 'Hai ricevuto un nuovo commento sulla poesia "' . $commentable->title . '".'
                    : 'Hai ricevuto un nuovo commento sull\'articolo "' . $commentable->title . '".',
                data: [
                    'comment_id' => $comment->id,
                    'commentable_type' => $commentableType,
                    'commentable_id' => $commentable->id,
                    'commentable_slug' => $slug,
                    'parent_id' => $comment->parent_id,
                ],
            );
        }

        if ($parent && $parent->user_id !== $comment->user_id && $parent->user_id !== $commentable->user_id) {
            $this->createNotificationAction->execute(
                userId: $parent->user_id,
                type: 'comment_replied',
                title: 'Nuova risposta a un tuo commento',
                message: 'Hai ricevuto una risposta a uno dei tuoi commenti.',
                data: [
                    'comment_id' => $comment->id,
                    'parent_comment_id' => $parent->id,
                    'commentable_type' => $commentableType,
                    'commentable_id' => $commentable->id,
                    'commentable_slug' => $slug,
                ],
            );
        }
    }
}
