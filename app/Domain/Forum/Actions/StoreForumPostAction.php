<?php

namespace Domain\Forum\Actions;

use Domain\Engagement\Actions\CastVoteAction;
use Domain\Forum\Enums\ForumPostStatus;
use Domain\Forum\Models\ForumCategory;
use Domain\Forum\Models\ForumPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreForumPostAction
{
    public function __construct(
        private readonly CastVoteAction $castVoteAction,
    ) {}

    public function execute(array $data, int $userId): ForumPost
    {
        $category = ForumCategory::query()->findOrFail($data['forum_category_id']);

        if (! $category->is_active) {
            throw ValidationException::withMessages([
                'forum_category_id' => [__('forum.errors.inactive_category')],
            ]);
        }

        return DB::transaction(function () use ($data, $userId, $category) {
            $post = ForumPost::query()->create([
                'user_id' => $userId,
                'forum_category_id' => $category->id,
                'title' => $data['title'],
                'slug' => $this->generateUniqueSlug($category->id, $data['title']),
                'body' => $data['body'],
                'status' => ForumPostStatus::Published,
                'is_locked' => false,
                'is_pinned' => false,
                'score' => 0,
                'comments_count' => 0,
            ]);

            $post->load('category');
            $this->castVoteAction->execute($post, $userId, 1);

            audit()->log(
                event: 'forum_post.created',
                userId: $userId,
                auditable: $post,
                newValues: [
                    'forum_category_id' => $post->forum_category_id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'status' => $post->status?->value,
                ],
                metadata: [
                    'source' => 'forum_post_store',
                ],
            );

            return $post->fresh(['user.profile', 'category.translations', 'votes']);
        });
    }

    private function generateUniqueSlug(int $categoryId, string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'post';
        $originalSlug = $slug;
        $counter = 2;

        while (ForumPost::query()
            ->where('forum_category_id', $categoryId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
