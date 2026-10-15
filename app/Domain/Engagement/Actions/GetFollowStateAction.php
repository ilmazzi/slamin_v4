<?php

namespace Domain\Engagement\Actions;

use Domain\Engagement\Models\Follow;
use Illuminate\Database\Eloquent\Model;

class GetFollowStateAction
{
    /**
     * @return array{is_following: bool, followers_count: int}
     */
    public function execute(Model $followable, ?int $userId): array
    {
        $followers = Follow::query()
            ->where('followable_type', $followable->getMorphClass())
            ->where('followable_id', $followable->getKey());

        return [
            'is_following' => $userId !== null && (clone $followers)->where('user_id', $userId)->exists(),
            'followers_count' => $followers->count(),
        ];
    }
}
