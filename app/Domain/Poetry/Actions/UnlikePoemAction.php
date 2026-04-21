<?php

namespace Domain\Poetry\Actions;

use Domain\Engagement\Models\Like;
use Domain\Poetry\Models\Poem;

class UnlikePoemAction
{
    public function execute(Poem $poem, int $userId): void
    {
        Like::query()
            ->where('user_id', $userId)
            ->where('likeable_type', $poem->getMorphClass())
            ->where('likeable_id', $poem->id)
            ->delete();

        audit()->log(
            event: 'poem.unliked',
            userId: $userId,
            auditable: $poem,
            metadata: [
                'source' => 'poem_unlike',
            ],
        );
    }
}
