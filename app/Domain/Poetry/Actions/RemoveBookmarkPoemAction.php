<?php

namespace Domain\Poetry\Actions;

use Domain\Engagement\Models\Bookmark;
use Domain\Poetry\Models\Poem;

class RemoveBookmarkPoemAction
{
    public function execute(Poem $poem, int $userId): void
    {
        Bookmark::query()
            ->where('user_id', $userId)
            ->where('bookmarkable_type', $poem->getMorphClass())
            ->where('bookmarkable_id', $poem->id)
            ->delete();

        audit()->log(
            event: 'poem.unbookmarked',
            userId: $userId,
            auditable: $poem,
            metadata: [
                'source' => 'poem_unbookmark',
            ],
        );
    }
}
