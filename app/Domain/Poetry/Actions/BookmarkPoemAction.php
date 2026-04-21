<?php

namespace Domain\Poetry\Actions;

use Domain\Engagement\Models\Bookmark;
use Domain\Poetry\Models\Poem;

class BookmarkPoemAction
{
    public function execute(Poem $poem, int $userId): void
    {
        Bookmark::query()->firstOrCreate([
            'user_id' => $userId,
            'bookmarkable_type' => $poem->getMorphClass(),
            'bookmarkable_id' => $poem->id,
        ]);

        audit()->log(
            event: 'poem.bookmarked',
            userId: $userId,
            auditable: $poem,
            metadata: [
                'source' => 'poem_bookmark',
            ],
        );
    }
}
