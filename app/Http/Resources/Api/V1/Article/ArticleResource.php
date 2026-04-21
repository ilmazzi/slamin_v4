<?php

namespace App\Http\Resources\Api\V1\Article;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \Domain\Article\Models\Article */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk('public');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'article_category_id' => $this->article_category_id,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->translatedName(),
                'slug' => $this->category->slug,
            ] : null,
            'cover_image_path' => $this->cover_image_path,
            'cover_image_url' => $this->cover_image_path ? $disk->url($this->cover_image_path) : null,
            'language' => $this->language,
            'status' => $this->status?->value,
            'is_public' => $this->is_public,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'bookmarks_count' => (int) ($this->bookmarks_count ?? 0),
            'is_liked' => (bool) ($this->is_liked ?? false),
            'is_bookmarked' => (bool) ($this->is_bookmarked ?? false),
            'author' => [
                'id' => $this->user?->id,
                'public_id' => $this->user?->public_id,
                'nickname' => $this->user?->profile?->nickname,
                'display_name' => $this->user?->profile?->display_name,
            ],
        ];
    }
}
