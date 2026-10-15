<?php

namespace Domain\Engagement\Actions;

use Domain\Engagement\Models\Follow;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class FollowAction
{
    public function __construct(
        private readonly GetFollowStateAction $followState,
        private readonly NotifyUserFollowedAction $notifyUserFollowed,
    ) {}

    /**
     * @return array{is_following: bool, followers_count: int}
     */
    public function execute(Model $followable, int $userId): array
    {
        $follow = Follow::query()->firstOrCreate([
            'user_id' => $userId,
            'followable_type' => $followable->getMorphClass(),
            'followable_id' => $followable->getKey(),
        ]);

        if ($follow->wasRecentlyCreated && $followable instanceof User) {
            $this->notifyUserFollowed->execute($followable, $userId);
        }

        return $this->followState->execute($followable, $userId);
    }
}
