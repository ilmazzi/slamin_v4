<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\Poem;

class DeletePoemAction
{
    public function execute(Poem $poem, int $userId): void
    {
        $poem->delete();

        audit()->log(
            event: 'poem.deleted',
            userId: $userId,
            auditable: $poem,
            metadata: [
                'source' => 'poem_delete',
            ],
        );
    }
}
