<?php

namespace App\Http\Resources\Api\V1\Gig;

use Domain\Event\Models\Event;
use Domain\Gig\Models\Gig;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Gig */
class GigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;
        $attributes = $this->resource->getAttributes();

        return [
            'id' => $this->id,
            'giggable_type' => $this->giggable_type,
            'giggable_id' => $this->giggable_id,
            'type' => $this->type?->value,
            'quantity' => $this->quantity,
            'filled_quantity' => $this->filled_quantity,
            'language' => $this->language,
            'hospitality_required' => (bool) $this->hospitality_required,
            'description' => $this->description,
            'proposed_price' => $this->proposed_price,
            'currency' => $this->currency,
            'deadline' => $this->deadline?->toISOString(),
            'special_requirements' => $this->special_requirements,
            'cachet_enabled' => (bool) $this->cachet_enabled,
            'travel_expenses_enabled' => (bool) $this->travel_expenses_enabled,
            'travel_expenses_max' => $this->travel_expenses_max,
            'accommodation_enabled' => (bool) $this->accommodation_enabled,
            'accommodation_details' => $this->accommodation_details,
            'status' => $this->status?->value,
            'opened_at' => $this->opened_at?->toISOString(),
            'filled_at' => $this->filled_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'applications_count' => $this->whenCounted('applications'),
            'pending_applications_count' => $this->when(
                array_key_exists('pending_applications_count', $attributes),
                fn (): int => (int) $attributes['pending_applications_count'],
            ),
            'my_application' => $this->when(
                array_key_exists('my_application_id', $attributes),
                fn (): ?array => $attributes['my_application_id'] === null ? null : [
                    'id' => (int) $attributes['my_application_id'],
                    'status' => $attributes['my_application_status'] ?? null,
                ],
            ),
            'can_manage' => $this->when(
                $this->relationLoaded('giggable'),
                fn (): bool => $this->resource->isManagedBy($viewerId === null ? null : (int) $viewerId),
            ),
            'parent' => $this->whenLoaded('giggable', fn (): ?array => $this->parentPayload()),
        ];
    }

    private function parentPayload(): ?array
    {
        $parent = $this->giggable;
        $disk = Storage::disk('public');

        if ($parent instanceof Event) {
            $venue = $parent->relationLoaded('venueDetail') ? $parent->venueDetail : null;
            $startsAt = null;

            if ($parent->relationLoaded('occurrences')) {
                $occurrence = $parent->occurrences->firstWhere('is_primary', true)
                    ?? $parent->occurrences->sortBy('starts_at')->first();
                $startsAt = $occurrence?->starts_at?->toISOString();
            }

            return [
                'type' => 'event',
                'id' => $parent->id,
                'title' => $parent->title,
                'slug' => $parent->slug,
                'url' => $parent->slug ? '/events/'.$parent->slug : null,
                'image_url' => $parent->cover_image_path ? $disk->url($parent->cover_image_path) : null,
                'city' => $venue?->city,
                'place_name' => $venue?->place_name,
                'starts_at' => $startsAt,
                'language' => null,
                'owner' => $this->ownerPayload($parent),
            ];
        }

        if ($parent instanceof Poem) {
            return [
                'type' => 'poem',
                'id' => $parent->id,
                'title' => $parent->title,
                'slug' => $parent->slug,
                'url' => $parent->slug ? '/poems/'.$parent->slug : null,
                'image_url' => $parent->image_path ? $disk->url($parent->image_path) : null,
                'city' => null,
                'place_name' => null,
                'starts_at' => null,
                'language' => $parent->language,
                'owner' => $this->ownerPayload($parent),
            ];
        }

        return null;
    }

    private function ownerPayload(Event|Poem $parent): ?GigUserResource
    {
        if (! $parent->relationLoaded('user') || $parent->user === null) {
            return null;
        }

        return new GigUserResource($parent->user);
    }
}
