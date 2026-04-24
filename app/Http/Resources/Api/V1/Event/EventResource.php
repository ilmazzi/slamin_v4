<?php

namespace App\Http\Resources\Api\V1\Event;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \Domain\Event\Models\Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk('public'); /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */


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
            'has_cost' => $this->has_cost,
            'price' => $this->price,
            'currency' => $this->currency,
            'promo_video_url' => $this->promo_video_url,
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
            'group_ids' => $this->groupLinks->pluck('group_id')->values(),
            'participant_invites' => $this->participantInvites->map(fn ($invite) => [
                'id' => $invite->id,
                'user_id' => $invite->user_id,
                'name' => $invite->name,
                'email' => $invite->email,
                'role' => $invite->role,
                'invite_type' => $invite->invite_type,
                'status' => $invite->status,
            ])->values(),
            'audience_invites' => $this->audienceInvites->map(fn ($invite) => [
                'id' => $invite->id,
                'user_id' => $invite->user_id,
                'status' => $invite->status,
            ])->values(),
            'author' => [
                'id' => $this->user?->id,
                'public_id' => $this->user?->public_id,
                'nickname' => $this->user?->profile?->nickname,
                'display_name' => $this->user?->profile?->display_name,
            ],
        ];
    }
}
