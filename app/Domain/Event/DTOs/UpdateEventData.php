<?php

namespace Domain\Event\DTOs;

use App\Http\Requests\Api\V1\Event\StoreEventRequest;
use Domain\Event\Enums\EventDeliveryMode;
use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Enums\EventStatus;
use Illuminate\Http\UploadedFile;

class UpdateEventData extends StoreEventData
{
    public function __construct(
        int $userId,
        ?int $eventCategoryId,
        ?int $festivalEventId,
        string $title,
        ?string $subtitle,
        string $description,
        ?string $requirements,
        EventScheduleMode $scheduleMode,
        EventDeliveryMode $deliveryMode,
        ?UploadedFile $coverImage,
        bool $hasCost,
        ?float $price,
        ?string $currency,
        ?string $promoVideoUrl,
        ?string $registrationDeadline,
        bool $publicInviteEnabled,
        bool $audienceCapacityEnabled,
        ?int $audienceCapacity,
        string $language,
        EventStatus $status,
        bool $isPublic,
        ?string $publishedAt,
        array $groupIds,
        array $participantInvites,
        array $audienceInvites,
        array $gigs,
        ?array $occurrence,
        ?array $recurrence,
        ?array $availabilityPoll,
        array $availabilityOptions,
        ?array $venue,
        ?array $online,
        public readonly bool $removeCoverImage,
    ) {
        parent::__construct(
            $userId,
            $eventCategoryId,
            $festivalEventId,
            $title,
            $subtitle,
            $description,
            $requirements,
            $scheduleMode,
            $deliveryMode,
            $coverImage,
            $hasCost,
            $price,
            $currency,
            $promoVideoUrl,
            $registrationDeadline,
            $publicInviteEnabled,
            $audienceCapacityEnabled,
            $audienceCapacity,
            $language,
            $status,
            $isPublic,
            $publishedAt,
            $groupIds,
            $participantInvites,
            $audienceInvites,
            $gigs,
            $occurrence,
            $recurrence,
            $availabilityPoll,
            $availabilityOptions,
            $venue,
            $online,
        );
    }

    public static function fromRequest(StoreEventRequest $request): self
    {

        $base = StoreEventData::fromRequest($request);

        return new self(
            $base->userId,
            $base->eventCategoryId,
            $base->festivalEventId,
            $base->title,
            $base->subtitle,
            $base->description,
            $base->requirements,
            $base->scheduleMode,
            $base->deliveryMode,
            $base->coverImage,
            $base->hasCost,
            $base->price,
            $base->currency,
            $base->promoVideoUrl,
            $base->registrationDeadline,
            $base->publicInviteEnabled,
            $base->audienceCapacityEnabled,
            $base->audienceCapacity,
            $base->language,
            $base->status,
            $base->isPublic,
            $base->publishedAt,
            $base->groupIds,
            $base->participantInvites,
            $base->audienceInvites,
            $base->gigs,
            $base->occurrence,
            $base->recurrence,
            $base->availabilityPoll,
            $base->availabilityOptions,
            $base->venue,
            $base->online,
            (bool) $request->validated('remove_cover_image', false),
        );
    }
}
