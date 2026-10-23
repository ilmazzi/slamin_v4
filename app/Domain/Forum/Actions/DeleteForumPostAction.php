<?php

namespace Domain\Forum\Actions;

use Domain\Forum\Models\ForumPost;

class DeleteForumPostAction
{
    public function execute(ForumPost $post, int $userId): void
    {
        $oldValues = [
            'forum_category_id' => $post->forum_category_id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status?->value,
        ];

        $post->delete();

        audit()->log(
            event: 'forum_post.deleted',
            userId: $userId,
            auditable: $post,
            oldValues: $oldValues,
            metadata: [
                'source' => 'forum_post_delete',
            ],
        );
    }
}
