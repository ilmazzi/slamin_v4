<?php

namespace App\Http\Resources\Api\V1\Article;

use App\Http\Resources\Api\V1\ContentTranslation\ContentTranslationResource;
use Domain\Article\Models\Article;
use Domain\Group\Support\VisibleAcceptedGroups;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Article */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk('public'); /** @var FilesystemAdapter $disk */
        $authUserId = $request->user()?->id;
        $isOwner = $authUserId && (int) $this->user_id === (int) $authUserId;

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
            'views_count' => (int) ($this->views_count ?? 0),
            'is_liked' => (bool) ($this->is_liked ?? false),
            'is_bookmarked' => (bool) ($this->is_bookmarked ?? false),
            'translations' => $this->whenLoaded('translations', fn () => $this->translations
                ->map(fn ($translation) => ['locale' => $translation->locale, 'title' => $translation->title])
                ->values()),
            'translation' => $this->whenLoaded('currentTranslation', fn () => $this->currentTranslation
                ? new ContentTranslationResource($this->currentTranslation)
                : null),
            'groups' => $this->relationLoaded('groupLinks')
                ? VisibleAcceptedGroups::map($this->groupLinks, $authUserId, $disk)
                : [],
            'group_ids' => $this->when($isOwner && $this->relationLoaded('groupLinks'), fn () => $this->groupLinks
                ->filter(fn ($link) => ! in_array($link->status?->value, ['rejected', 'removed'], true))
                ->pluck('group_id')
                ->values()),
            'group_affiliations' => $this->when($isOwner && $this->relationLoaded('groupLinks'), fn () => $this->groupLinks->map(fn ($link) => [
                'id' => $link->id,
                'group_id' => $link->group_id,
                'status' => $link->status?->value,
                'group' => $link->group ? [
                    'id' => $link->group->id,
                    'slug' => $link->group->slug,
                    'name' => $link->group->name,
                ] : null,
            ])->values()),
            'author' => [
                'id' => $this->user?->id,
                'public_id' => $this->user?->public_id,
                'nickname' => $this->user?->profile?->nickname,
                'display_name' => $this->user?->profile?->display_name,
                'avatar_url' => $this->user?->profile?->avatar_path
                    ? $disk->url($this->user->profile->avatar_path)
                    : null,
            ],
        ];
    }
}
