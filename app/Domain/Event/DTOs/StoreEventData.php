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
        public readonly array $audienceInvites,
        public readonly array $gigs,
        public readonly ?array $occurrence,
        public readonly ?array $recurrence,
        public readonly ?array $availabilityPoll,
        public readonly array $availabilityOptions,
        public readonly ?array $venue,
        public readonly ?array $online,
        public readonly array $collaboratorUserIds = [],
        public readonly ?string $externalLinkUrl = null,
        public readonly array $galleryImages = [],
    ) {}

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
            audienceInvites: self::normalizeAudienceInvites(
                $request->validated('audience_invites', []),
                $request->validated('audience_invite_user_ids', []),
            ),
            gigs: $request->validated('gigs', []),
            occurrence: $request->validated('occurrence'),
            recurrence: $request->validated('recurrence'),
            availabilityPoll: $request->validated('availability_poll'),
            availabilityOptions: $request->validated('availability_options', []),
            venue: $request->validated('venue'),
            online: $request->validated('online'),
            collaboratorUserIds: collect($request->validated('collaborator_user_ids', []))
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all(),
            externalLinkUrl: $request->validated('external_link_url'),
            galleryImages: self::uploadedFiles($request, 'gallery_images'),
        );
    }

    private static function uploadedFiles(StoreEventRequest $request, string $key): array
    {
        $files = $request->file($key);
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return collect(is_array($files) ? $files : [])
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->values()
            ->all();
    }

    private static function normalizeAudienceInvites(array $invites, array $legacyUserIds): array
    {
        $normalized = collect($invites)
            ->map(fn (array $invite): array => [
                'user_id' => $invite['invite_type'] === 'internal' ? (int) $invite['user_id'] : null,
                'name' => $invite['invite_type'] === 'external' ? $invite['name'] : null,
                'email' => $invite['invite_type'] === 'external' ? mb_strtolower($invite['email']) : null,
                'invite_type' => $invite['invite_type'],
                'status' => $invite['status'] ?? 'pending',
            ]);

        $legacy = collect($legacyUserIds)
            ->map(fn (int|string $userId): array => [
                'user_id' => (int) $userId,
                'name' => null,
                'email' => null,
                'invite_type' => 'internal',
                'status' => 'pending',
            ]);

        return $normalized
            ->concat($legacy)
            ->unique(fn (array $invite): string => $invite['invite_type'] === 'internal'
                ? 'user:'.$invite['user_id']
                : 'email:'.$invite['email'])
            ->values()
            ->all();
    }
}
