<?php

namespace App\Http\Resources\Api\V1\Poetry;

use Domain\Poetry\Models\Poem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Poem */
class PoemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk('public'); /** @var FilesystemAdapter $disk */

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'description' => $this->description,

            'poem_category_id' => $this->poem_category_id,
            'poem_type_id' => $this->poem_type_id,

            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->translatedName(),
                'slug' => $this->category->slug,
            ] : null,

            'type' => $this->type ? [
                'id' => $this->type->id,
                'name' => $this->type->translatedName(),
                'slug' => $this->type->slug,
            ] : null,

            'image_path' => $this->image_path,
            'image_url' => $this->image_path
                ? $disk->url($this->image_path)
                : null,
            'language' => $this->language,
            'status' => $this->status?->value,
            'is_public' => $this->is_public,
            'is_featured' => (bool) ($this->is_featured ?? false),
            'featured_rank' => (int) ($this->featured_rank ?? 0),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'likes_count' => (int) ($this->likes_count ?? 0),
            'bookmarks_count' => (int) ($this->bookmarks_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'views_count' => (int) ($this->views_count ?? 0),
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
