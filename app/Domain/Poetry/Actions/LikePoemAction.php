<?php

namespace Domain\Poetry\Actions;

use Domain\Engagement\Models\Like;
use Domain\Poetry\Models\Poem;

class LikePoemAction
{
    public function execute(Poem $poem, int $userId): void
    {
        Like::query()->firstOrCreate([
            'user_id' => $userId,
            'likeable_type' => $poem->getMorphClass(),
            'likeable_id' => $poem->id,
        ]);

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
