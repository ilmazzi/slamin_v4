<?php

namespace Domain\Forum\Actions;

use Domain\Forum\Enums\ForumPostStatus;
use Domain\Forum\Models\ForumPost;
use Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateForumPostAction
{
    public function execute(ForumPost $post, array $data, User $user): ForumPost
    {
        return DB::transaction(function () use ($post, $data, $user) {
            $oldValues = [
                'title' => $post->title,
                'body' => $post->body,
                'status' => $post->status?->value,
                'is_locked' => $post->is_locked,
                'is_pinned' => $post->is_pinned,
            ];

            $payload = [];

            if (array_key_exists('title', $data)) {
                $payload['title'] = $data['title'];
            }

            if (array_key_exists('body', $data)) {
                $payload['body'] = $data['body'];
            }

            if ($user->can('moderate', $post)) {
                if (array_key_exists('status', $data) && $data['status'] !== null) {
                    $payload['status'] = ForumPostStatus::from($data['status']);
                }

                if (array_key_exists('is_locked', $data) && $data['is_locked'] !== null) {
                    $payload['is_locked'] = (bool) $data['is_locked'];
                }

                if (array_key_exists('is_pinned', $data) && $data['is_pinned'] !== null) {
                    $payload['is_pinned'] = (bool) $data['is_pinned'];
                }
            }

            if ($payload !== []) {
                $post->update($payload);
            }

            audit()->log(
                event: 'forum_post.updated',
                userId: $user->id,
                auditable: $post,
                oldValues: $oldValues,
                newValues: [
                    'title' => $post->title,
                    'body' => $post->body,
                    'status' => $post->status?->value,
                    'is_locked' => $post->is_locked,
                    'is_pinned' => $post->is_pinned,
                ],
                metadata: [
                    'source' => $user->can('moderate', $post) ? 'forum_post_moderate' : 'forum_post_update',
                ],
            );

            return $post->fresh(['user.profile', 'category.translations']);
        });
    }
}
