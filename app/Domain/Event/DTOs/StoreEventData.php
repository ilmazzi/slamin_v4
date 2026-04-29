<?php

namespace Domain\Event\DTOs;

use App\Http\Requests\Api\V1\Event\StoreEventRequest;
use Domain\Event\Enums\EventDeliveryMode;
use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Enums\EventStatus;
use Illuminate\Http\UploadedFile;

class StoreEventData
{
    public function __construct(
        public readonly int $userId,
        public readonly ?int $eventCategoryId,
        public readonly ?int $festivalEventId,
        public readonly string $title,
        public readonly ?string $subtitle,
        public readonly string $description,
        public readonly ?string $requirements,
        public readonly EventScheduleMode $scheduleMode,
        public readonly EventDeliveryMode $deliveryMode,
        public readonly ?UploadedFile $coverImage,
        public readonly bool $hasCost,
        public readonly ?float $price,
        public readonly ?string $currency,
        public readonly ?string $promoVideoUrl,
        public readonly ?string $registrationDeadline,
        public readonly bool $publicInviteEnabled,
        public readonly bool $audienceCapacityEnabled,
        public readonly ?int $audienceCapacity,
        public readonly string $language,
        public readonly EventStatus $status,
        public readonly bool $isPublic,
        public readonly ?string $publishedAt,
        public readonly array $groupIds,
        public readonly array $participantInvites,
        public readonly array $audienceInviteUserIds,
        public readonly array $gigs,
        public readonly ?array $occurrence,
        public readonly ?array $recurrence,
        public readonly ?array $availabilityPoll,
        public readonly array $availabilityOptions,
        public readonly ?array $venue,
        public readonly ?array $online,
    ) {
    }

    public static function fromRequest(StoreEventRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            eventCategoryId: $request->validated('event_category_id'),
            festivalEventId: $request->validated('festival_event_id'),
            title: $request->validated('title'),
            subtitle: $request->validated('subtitle'),
            description: $request->validated('description'),
            requirements: $request->validated('requirements'),
            scheduleMode: EventScheduleMode::from($request->validated('schedule_mode')),
            deliveryMode: EventDeliveryMode::from($request->validated('delivery_mode')),
            coverImage: $request->file('cover_image'),
            hasCost: (bool) $request->validated('has_cost', false),
            price: $request->validated('price') !== null ? (float) $request->validated('price') : null,
            currency: $request->validated('currency'),
            promoVideoUrl: $request->validated('promo_video_url'),
            registrationDeadline: $request->validated('registration_deadline'),
            publicInviteEnabled: (bool) $request->validated('public_invite_enabled', false),
            audienceCapacityEnabled: (bool) $request->validated('audience_capacity_enabled', false),
            audienceCapacity: $request->validated('audience_capacity'),
            language: $request->validated('language', 'it'),
            status: $request->validated('status')
                ? EventStatus::from($request->validated('status'))
                : EventStatus::Draft,
            isPublic: (bool) $request->validated('is_public', true),
            publishedAt: $request->validated('published_at'),
            groupIds: $request->validated('group_ids', []),
            participantInvites: $request->validated('participant_invites', []),
            audienceInviteUserIds: $request->validated('audience_invite_user_ids', []),
            gigs: $request->validated('gigs', []),
            occurrence: $request->validated('occurrence'),
            recurrence: $request->validated('recurrence'),
            availabilityPoll: $request->validated('availability_poll'),
            availabilityOptions: $request->validated('availability_options', []),
            venue: $request->validated('venue'),
            online: $request->validated('online'),
        );
    }
}
