<?php

namespace Domain\Moderation\Actions;

use Domain\Poetry\Models\Poem;

class ShowModerationPoemAction
{
    public function execute(Poem $poem): Poem
    {
        return $poem->load([
            'user.profile',
            'category.translations',
            'type.translations',
            'moderationDecisions.moderator.profile',
        ])->loadCount(['likes', 'bookmarks']);
    }
}
