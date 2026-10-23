<?php

namespace App\Http\Resources\Api\V1\Event;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Group\Support\VisibleAcceptedGroups;
use Domain\Share\Support\SharePublicUrl;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk('public'); /** @var FilesystemAdapter $disk */
        $authUserId = auth('sanctum')->id();
        $viewerId = $authUserId === null ? null : (int) $authUserId;
        $isOwner = $this->resource->isOwnedBy($viewerId);
        $canManage = $this->resource->isManagedBy($viewerId);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'slug' => $this->slug,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'event_category_id' => $this->event_category_id,
            'festival_event_id' => $this->festival_event_id,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->translatedName(),
                'slug' => $this->category->slug,
            ] : null,
            'festival' => $this->festival ? [
                'id' => $this->festival->id,
                'title' => $this->festival->title,
                'slug' => $this->festival->slug,
            ] : null,
            'schedule_mode' => $this->schedule_mode?->value,
            'delivery_mode' => $this->delivery_mode?->value,
            'cover_image_path' => $this->cover_image_path,
            'cover_image_url' => $this->cover_image_path ? $disk->url($this->cover_image_path) : null,
            'gallery_images' => collect($this->gallery_image_paths ?? [])
                ->filter(fn ($path) => is_string($path) && $path !== '')
                ->map(fn ($path) => [
                    'path' => $path,
                    'url' => $disk->url($path),
                ])
                ->values(),
            'share_url' => $this->status === EventStatus::Published && $this->is_public
                ? SharePublicUrl::event($this->slug)
                : null,
            'share_card_url' => $this->status === EventStatus::Published && $this->is_public
                ? route('share.events.card', ['slug' => $this->slug])
                : null,
            'has_cost' => $this->has_cost,
            'price' => $this->price,
            'currency' => $this->currency,
            'promo_video_url' => $this->promo_video_url,
            'external_link_url' => $this->external_link_url,
            'registration_deadline' => $this->registration_deadline?->toISOString(),
            'public_invite_enabled' => $this->public_invite_enabled,
            'audience_capacity_enabled' => $this->audience_capacity_enabled,
            'audience_capacity' => $this->audience_capacity,
            'language' => $this->language,
            'status' => $this->status?->value,
            'is_public' => $this->is_public,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'views_count' => (int) ($this->views_count ?? 0),
            'occurrences' => $this->occurrences->map(fn ($occurrence) => [
                'id' => $occurrence->id,
                'starts_at' => $occurrence->starts_at?->toISOString(),
                'ends_at' => $occurrence->ends_at?->toISOString(),
                'timezone' => $occurrence->timezone,
                'is_primary' => $occurrence->is_primary,
            ])->values(),
            'recurrence' => $this->recurrence ? [
                'frequency' => $this->recurrence->frequency?->value,
                'interval' => $this->recurrence->interval,
                'occurrences_count' => $this->recurrence->occurrences_count,
                'ends_at' => $this->recurrence->ends_at?->toISOString(),
            ] : null,
            'availability_poll' => $this->availabilityPoll ? [
                'response_deadline' => $this->availabilityPoll->response_deadline?->toISOString(),
                'instructions' => $this->availabilityPoll->instructions,
                'is_finalized' => $this->availabilityOptions->contains(
                    fn ($option) => $option->finalized_at !== null
                ),
            ] : null,
            'availability_options' => $this->availabilityOptions->map(fn ($option) => [
                'id' => $option->id,
                'starts_at' => $option->starts_at?->toISOString(),
                'ends_at' => $option->ends_at?->toISOString(),
                'timezone' => $option->timezone,
                'description' => $option->description,
                'finalized_at' => $option->finalized_at?->toISOString(),
                'is_final' => $option->finalized_at !== null,
                'votes_count' => $option->votes->count(),
                'current_user_has_voted' => $request->user() !== null
                    ? $option->votes->contains('user_id', $request->user()->id)
                    : false,
            ])->values(),
            'venue' => $this->venueDetail ? [
                'place_name' => $this->venueDetail->place_name,
                'country_code' => $this->venueDetail->country_code,
                'city' => $this->venueDetail->city,
                'postal_code' => $this->venueDetail->postal_code,
                'address_line' => $this->venueDetail->address_line,
                'latitude' => $this->venueDetail->latitude,
                'longitude' => $this->venueDetail->longitude,
            ] : null,
            'online' => $this->onlineDetail ? [
                'meeting_url' => $this->onlineDetail->meeting_url,
                'timezone' => $this->onlineDetail->timezone,
            ] : null,
            'groups' => VisibleAcceptedGroups::map($this->groupLinks, $authUserId, $disk),
            'group_ids' => $this->when($canManage, fn () => $this->groupLinks
                ->filter(fn ($link) => ! in_array($link->status?->value, ['rejected', 'removed'], true))
                ->pluck('group_id')
                ->values()),
            'group_affiliations' => $this->when($canManage, fn () => $this->groupLinks->map(fn ($link) => [
                'id' => $link->id,
                'group_id' => $link->group_id,
                'status' => $link->status?->value,
                'group' => $link->group ? [
                    'id' => $link->group->id,
                    'slug' => $link->group->slug,
                    'name' => $link->group->name,
                ] : null,
            ])->values()),
            'participant_invites' => $this->when($canManage, fn () => $this->participantInvites->map(fn ($invite) => [
                'id' => $invite->id,
                'user_id' => $invite->user_id,
                'name' => $invite->name,
                'email' => $invite->email,
                'role' => $invite->role,
                'invite_type' => $invite->invite_type,
                'status' => $invite->status,
            ])->values()),
            'audience_invites' => $this->when($canManage, fn () => $this->audienceInvites->map(fn ($invite) => [
                'id' => $invite->id,
                'user_id' => $invite->user_id,
                'name' => $invite->name,
                'email' => $invite->email,
                'invite_type' => $invite->invite_type,
                'status' => $invite->status,
                'invited_user' => $invite->user ? [
                    'id' => $invite->user->id,
                    'public_id' => $invite->user->public_id,
                    'nickname' => $invite->user->profile?->nickname,
                    'display_name' => $invite->user->profile?->display_name,
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
            'collaborators' => $this->whenLoaded('collaborators', fn () => $this->collaborators->map(fn ($collaborator) => [
                'id' => $collaborator->id,
                'public_id' => $collaborator->public_id,
                'nickname' => $collaborator->profile?->nickname,
                'display_name' => $collaborator->profile?->display_name,
                'avatar_url' => $collaborator->profile?->avatar_path
                    ? $disk->url($collaborator->profile->avatar_path)
                    : null,
            ])->values()),
            'is_owner' => $isOwner,
            'can_manage' => $canManage,
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'bookmarks_count' => (int) ($this->bookmarks_count ?? 0),
            'is_liked' => (bool) ($this->is_liked ?? false),
            'is_bookmarked' => (bool) ($this->is_bookmarked ?? false),
            $this->mergeWhen($this->followers_count !== null, fn () => [
                'followers_count' => (int) $this->followers_count,
                'is_following' => (bool) $this->is_following,
            ]),
        ];
    }
}
