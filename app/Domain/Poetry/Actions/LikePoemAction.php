<?php

namespace Domain\Poetry\Actions;

use Domain\Engagement\Actions\NotifyContentLikedAction;
use Domain\Engagement\Models\Like;
use Domain\Poetry\Models\Poem;

class LikePoemAction
{
    public function __construct(
        private readonly NotifyContentLikedAction $notifyContentLikedAction,
    ) {}

    public function execute(Poem $poem, int $userId): void
    {
        $like = Like::query()->firstOrCreate([
            'user_id' => $userId,
            'likeable_type' => $poem->getMorphClass(),
            'likeable_id' => $poem->id,
        ]);

        $this->notifyContentLikedAction->execute($like, $poem);

        audit()->log(
            event: 'poem.liked',
            userId: $userId,
            auditable: $poem,
            metadata: [
                'source' => 'poem_like',
            ],
        );
    }
}
