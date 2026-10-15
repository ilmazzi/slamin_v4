<?php

namespace Domain\Engagement\Actions;

use Domain\Engagement\Models\Follow;
use Illuminate\Database\Eloquent\Model;

class UnfollowAction
{
    public function __construct(
        private readonly GetFollowStateAction $followState,
    ) {}

    /**
     * @return array{is_following: bool, followers_count: int}
     */
    public function execute(Model $followable, int $userId): array
    {
        Follow::query()
            ->where('user_id', $userId)
            ->where('followable_type', $followable->getMorphClass())
            ->where('followable_id', $followable->getKey())
            ->delete();

        return $this->followState->execute($followable, $userId);
    }
}
