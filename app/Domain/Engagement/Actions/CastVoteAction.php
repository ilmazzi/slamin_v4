<?php

namespace Domain\Engagement\Actions;

use Domain\Comment\Models\Comment;
use Domain\Engagement\Models\Vote;
use Domain\Forum\Models\ForumPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CastVoteAction
{
    public function execute(Model $votable, int $userId, int $value): Model
    {
        $this->ensureVotable($votable);

        if (! in_array($value, [-1, 0, 1], true)) {
            throw ValidationException::withMessages([
                'value' => [__('forum.errors.invalid_vote')],
            ]);
        }

        return DB::transaction(function () use ($votable, $userId, $value) {
            $locked = $votable->newQuery()
                ->whereKey($votable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $vote = Vote::query()
                ->where('user_id', $userId)
                ->where('votable_type', $locked->getMorphClass())
                ->where('votable_id', $locked->getKey())
                ->lockForUpdate()
                ->first();

            if ($value === 0) {
                $vote?->delete();
            } elseif ($vote) {
                $vote->update(['value' => $value]);
            } else {
                Vote::query()->create([
                    'user_id' => $userId,
                    'votable_type' => $locked->getMorphClass(),
                    'votable_id' => $locked->getKey(),
                    'value' => $value,
                ]);
            }

            $score = (int) Vote::query()
                ->where('votable_type', $locked->getMorphClass())
                ->where('votable_id', $locked->getKey())
                ->sum('value');

            $locked->forceFill(['score' => $score])->save();

            audit()->log(
                event: 'vote.cast',
                userId: $userId,
                auditable: $locked,
                newValues: [
                    'value' => $value,
                    'score' => $score,
                ],
                metadata: [
                    'source' => 'forum_vote',
                    'votable_type' => $locked->getMorphClass(),
                ],
            );

            return $locked;
        });
    }

    private function ensureVotable(Model $votable): void
    {
        if ($votable instanceof ForumPost) {
            $votable->loadMissing('category');
            if (! $votable->isPubliclyVisible()) {
                throw new NotFoundHttpException;
            }

            return;
        }

        if ($votable instanceof Comment) {
            $commentable = $votable->commentable;
            if ($commentable instanceof ForumPost) {
                $commentable->loadMissing('category');
                if ($commentable->isPubliclyVisible()) {
                    return;
                }
            }
        }

        throw new NotFoundHttpException;
    }
}
