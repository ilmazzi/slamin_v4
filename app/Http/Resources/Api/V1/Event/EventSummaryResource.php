<?php

namespace App\Http\Resources\Api\V1\Event;

use Domain\Event\Models\Event;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Event */
class EventSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk('public'); /** @var FilesystemAdapter $disk */
        $authUserId = auth('sanctum')->id();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->translatedName(),
                'slug' => $this->category->slug,
            ] : null,
            'cover_image_url' => $this->cover_image_path ? $disk->url($this->cover_image_path) : null,
            'has_cost' => $this->has_cost,
            'price' => $this->price,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'published_at' => $this->published_at?->toISOString(),
            'occurrences' => $this->occurrences->map(fn ($occurrence) => [
                'id' => $occurrence->id,
                'starts_at' => $occurrence->starts_at?->toISOString(),
                'ends_at' => $occurrence->ends_at?->toISOString(),
                'timezone' => $occurrence->timezone,
                'is_primary' => $occurrence->is_primary,
            ])->values(),
            'venue' => $this->venueDetail ? [
                'place_name' => $this->venueDetail->place_name,
                'city' => $this->venueDetail->city,
                'address_line' => $this->venueDetail->address_line,
                'latitude' => $this->venueDetail->latitude,
                'longitude' => $this->venueDetail->longitude,
            ] : null,
            'author' => $this->when($this->relationLoaded('user'), fn () => [
                'id' => $this->user?->id,
                'public_id' => $this->user?->public_id,
                'nickname' => $this->user?->profile?->nickname,
                'display_name' => $this->user?->profile?->display_name,
                'avatar_url' => $this->user?->profile?->avatar_path
                    ? $disk->url($this->user->profile->avatar_path)
                    : null,
            ]),
            'is_owner' => $authUserId !== null
                && (int) $authUserId === (int) $this->user_id,
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'bookmarks_count' => (int) ($this->bookmarks_count ?? 0),
            'is_liked' => (bool) ($this->is_liked ?? false),
            'is_bookmarked' => (bool) ($this->is_bookmarked ?? false),
        ];
    }
}
