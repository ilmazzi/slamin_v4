<?php

namespace Domain\Forum\Queries;

use Domain\Forum\Models\ForumPost;
use Domain\User\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowForumPostQuery
{
    public function byPublicId(string $publicId, ?int $viewerId = null): ForumPost
    {
        $post = ForumPost::query()
            ->with(['user.profile', 'category.translations'])
            ->where('public_id', $publicId)
            ->first();

        return $this->visibleOrFail($post, $viewerId);
    }

    public function bySlug(string $categorySlug, string $postSlug, ?int $viewerId = null): ForumPost
    {
        $post = ForumPost::query()
            ->with(['user.profile', 'category.translations'])
            ->where('slug', $postSlug)
            ->whereHas('category', fn ($category) => $category->where('slug', $categorySlug))
            ->first();

        return $this->visibleOrFail($post, $viewerId);
    }

    private function visibleOrFail(?ForumPost $post, ?int $viewerId): ForumPost
    {
        if (! $post) {
            throw new NotFoundHttpException;
        }

        $viewer = $viewerId ? User::query()->find($viewerId) : null;

        if (! (new \Domain\Forum\Policies\ForumPostPolicy)->view($viewer, $post)) {
            throw new NotFoundHttpException;
        }

        if ($viewerId) {
            $post->load(['votes' => fn ($votes) => $votes->where('user_id', $viewerId)]);
        }

        return $post;
    }
}
