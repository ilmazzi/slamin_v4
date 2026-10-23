<?php

namespace Domain\Forum\Queries;

use Domain\Comment\Models\Comment;
use Domain\Forum\Enums\ForumPostStatus;
use Domain\Forum\Models\ForumCategory;
use Domain\Forum\Models\ForumPost;
use Illuminate\Database\Eloquent\Collection;

class ListForumCategoryQuery
{
    public function execute(): Collection
    {
        $postMorph = (new ForumPost)->getMorphClass();

        return ForumCategory::query()
            ->select('forum_categories.*')
            ->with('translations')
            ->where('is_active', true)
            ->withCount(['posts as posts_count' => function ($query) {
                $query->where('status', ForumPostStatus::Published);
            }])
            ->selectSub(
                Comment::query()
                    ->selectRaw('count(*)')
                    ->where('commentable_type', $postMorph)
                    ->whereIn('commentable_id', ForumPost::query()
                        ->select('id')
                        ->whereColumn('forum_posts.forum_category_id', 'forum_categories.id')
                        ->where('status', ForumPostStatus::Published)
                    ),
                'comments_count'
            )
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
