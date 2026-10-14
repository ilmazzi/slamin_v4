<?php

namespace Domain\Event\Actions;

use Domain\Admin\Actions\ResolveContentPublicationFlowAction;
use Domain\Event\DTOs\UpdateEventData;
use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Shared\Enums\ContentType;
use Domain\Shared\Enums\PublicationFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateEventAction
{
    public function __construct(
        private readonly ResolveContentPublicationFlowAction $resolveContentPublicationFlowAction,
        private readonly UpsertRecentEventPlaceAction $upsertRecentEventPlaceAction,
        private readonly BuildEventInviteNotificationsAction $buildEventInviteNotificationsAction,
        private readonly NotifyEventInvitesAction $notifyEventInvitesAction,
        private readonly NotifyEventUpdatedAction $notifyEventUpdatedAction,
    ) {}

    public function execute(Event $event, UpdateEventData $data): Event
    {

        return DB::transaction(function () use ($event, $data) {
            $previousParticipantUserIds = $event->participantInvites()
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $previousAudienceUserIds = $event->audienceInvites()
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $previousExternalParticipantKeys = $event->participantInvites()
                ->where('invite_type', 'external')
                ->whereNotNull('email')
                ->get()
                ->map(fn ($invite) => strtolower((string) $invite->email).'|'.strtolower((string) $invite->role))
                ->all();
            $previousExternalAudienceEmails = $event->audienceInvites()
                ->where('invite_type', 'external')
                ->whereNotNull('email')
                ->pluck('email')
                ->map(fn ($email) => strtolower((string) $email))
                ->all();
            $updateRecipientUserIds = $this->notifyEventUpdatedAction->recipientUserIds($event);

            $oldValues = [
                'title' => $event->title,
                'event_category_id' => $event->event_category_id,
                'festival_event_id' => $event->festival_event_id,
                'schedule_mode' => $event->schedule_mode?->value,
                'delivery_mode' => $event->delivery_mode?->value,
                'status' => $event->status?->value,
                'is_public' => $event->is_public,
                'published_at' => $event->published_at?->toISOString(),
            ];

            $status = $this->resolveTargetStatus($data->status);
            $publishedAt = $status === EventStatus::Published
                ? ($data->publishedAt ? now()->parse($data->publishedAt) : ($event->published_at ?? now()))
                : null;

            $coverImagePath = $event->cover_image_path;

            if ($data->removeCoverImage && $coverImagePath) {
                Storage::disk('public')->delete($coverImagePath);
                $coverImagePath = null;
            }

            if ($data->coverImage) {
                if ($coverImagePath) {
                    Storage::disk('public')->delete($coverImagePath);
                }

                $coverImagePath = $data->coverImage->store('events', 'public');
            }

            $currentCategorySlug = $event->category?->slug;

            $newCategoryId = $data->eventCategoryId ?? $event->event_category_id;

            $newCategorySlug = $newCategoryId
                ? EventCategory::query()->whereKey($newCategoryId)->value('slug')
                : null;

            $hasChildFestivalEvents = $event->childFestivalEvents()->exists();

            if (
                $hasChildFestivalEvents &&
                $currentCategorySlug === 'festival' &&
                $newCategorySlug !== 'festival'
            ) {
                throw ValidationException::withMessages([
                    'event_category_id' => __('event.validation.festival_with_children_cannot_change_category'),
                ]);
            }
            $event->update([
                'event_category_id' => $data->eventCategoryId,
                'festival_event_id' => $data->festivalEventId,
                'title' => $data->title,
                'subtitle' => $data->subtitle,
                'description' => $data->description,
                'requirements' => $data->requirements,
                'schedule_mode' => $data->scheduleMode,
                'delivery_mode' => $data->deliveryMode,
                'cover_image_path' => $coverImagePath,
                'has_cost' => $data->hasCost,
                'price' => $data->hasCost ? $data->price : null,
                'currency' => $data->hasCost ? strtoupper((string) $data->currency) : null,
                'promo_video_url' => $data->promoVideoUrl,
                'registration_deadline' => $data->registrationDeadline,
                'public_invite_enabled' => $data->publicInviteEnabled,
                'audience_capacity_enabled' => $data->audienceCapacityEnabled,
                'audience_capacity' => $data->audienceCapacityEnabled ? $data->audienceCapacity : null,
                'language' => $data->language,
                'status' => $status,
                'is_public' => $data->isPublic,
                'published_at' => $publishedAt,
            ]);

            $this->resyncRelatedData($event, $data);
            $notifyPayload = $this->buildEventInviteNotificationsAction->execute(
                $event,
                $data,
                $previousParticipantUserIds,
                $previousAudienceUserIds,
                $previousExternalParticipantKeys,
                $previousExternalAudienceEmails,
            );

            audit()->log(
                event: 'event.updated',
                userId: $data->userId,
                auditable: $event,
                oldValues: $oldValues,
                newValues: [
                    'title' => $event->title,
                    'event_category_id' => $event->event_category_id,
                    'festival_event_id' => $event->festival_event_id,
                    'schedule_mode' => $event->schedule_mode?->value,
                    'delivery_mode' => $event->delivery_mode?->value,
                    'status' => $event->status?->value,
                    'is_public' => $event->is_public,
                    'published_at' => $event->published_at?->toISOString(),
                ],
                metadata: [
                    'source' => 'event_update',
                ],
            );

            $this->notifyEventInvitesAction->execute(
                $event,
                $notifyPayload['participantInvites'],
                $notifyPayload['externalParticipantInvites'],
                $notifyPayload['audienceInvites'],
                $notifyPayload['externalAudienceInvites'],
            );

            $newlyInvitedUserIds = $notifyPayload['participantInvites']
                ->pluck('user_id')
                ->concat($notifyPayload['audienceInvites']->pluck('user_id'))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->all();

            $this->notifyEventUpdatedAction->execute(
                $event,
                $updateRecipientUserIds,
                $newlyInvitedUserIds,
            );

            return $this->loadFullEvent($event);
        });
    }

    private function resyncRelatedData(Event $event, UpdateEventData $data): void
    {
        $event->occurrences()->delete();
        $event->recurrence()?->delete();
        $event->availabilityPoll()?->delete();
        $event->availabilityOptions()->delete();
        $event->venueDetail()?->delete();
        $event->onlineDetail()?->delete();
        $event->groupLinks()->delete();
        $event->participantInvites()->delete();
        $event->audienceInvites()->delete();

        if ($data->occurrence) {
            $event->occurrences()->create([
                'starts_at' => $data->occurrence['starts_at'],
                'ends_at' => $data->occurrence['ends_at'] ?? null,
                'timezone' => $data->occurrence['timezone'],
                'is_primary' => true,
            ]);
        }

        if ($data->scheduleMode === EventScheduleMode::Recurring && $data->recurrence) {
            $event->recurrence()->create($data->recurrence);
        }

        if ($data->scheduleMode === EventScheduleMode::Availability && $data->availabilityPoll) {
            $event->availabilityPoll()->create($data->availabilityPoll);
            foreach ($data->availabilityOptions as $option) {
                $event->availabilityOptions()->create([
                    'starts_at' => $option['starts_at'],
                    'ends_at' => $option['ends_at'] ?? null,
                    'timezone' => $option['timezone'] ?? 'UTC',
                    'description' => $option['description'] ?? null,
                ]);
            }
        }

        if ($data->deliveryMode->value === 'in_person' && $data->venue) {
            $event->venueDetail()->create([
                'place_name' => $data->venue['place_name'],
                'country_code' => strtoupper($data->venue['country_code']),
                'city' => $data->venue['city'],
                'postal_code' => $data->venue['postal_code'] ?? null,
                'address_line' => $data->venue['address_line'] ?? null,
                'latitude' => $data->venue['latitude'] ?? null,
                'longitude' => $data->venue['longitude'] ?? null,
            ]);

            $this->upsertRecentEventPlaceAction->execute($data->userId, $data->venue);
        }

        if ($data->deliveryMode->value === 'online' && $data->online) {
            $event->onlineDetail()->create($data->online);
        }

        if ($data->groupIds !== []) {
            $event->groupLinks()->createMany(
                collect($data->groupIds)->map(fn ($groupId) => ['group_id' => $groupId])->all()
            );
        }

        if ($data->participantInvites !== []) {
            $event->participantInvites()->createMany(
                collect($data->participantInvites)->map(fn ($invite) => [
                    'user_id' => $invite['user_id'] ?? null,
                    'name' => $invite['name'] ?? null,
                    'email' => $invite['email'] ?? null,
                    'role' => $invite['role'],
                    'invite_type' => $invite['invite_type'],
                    'status' => $invite['status'] ?? 'pending',
                ])->all()
            );
        }

        if ($data->audienceInvites !== []) {
            $event->audienceInvites()->createMany($data->audienceInvites);
        }
    }

    private function loadFullEvent(Event $event): Event
    {
        return $event->fresh([
            'user.profile',
            'category.translations',
            'festival',
            'occurrences',
            'recurrence',
            'availabilityPoll',
            'availabilityOptions.votes',
            'venueDetail',
            'onlineDetail',
            'groupLinks',
            'participantInvites.user.profile',
            'audienceInvites.user.profile',
        ]);
    }

    public function updatePublicationStatus(Event $event, EventStatus $requestedStatus, int $userId): Event
    {
        $status = $this->resolveTargetStatus($requestedStatus);
        $publishedAt = $status === EventStatus::Published
            ? ($event->published_at ?? now())
            : null;

        $event->update([
            'status' => $status,
            'is_public' => $status === EventStatus::Published ? true : $event->is_public,
            'published_at' => $publishedAt,
        ]);

        audit()->log(
            event: 'event.updated',
            userId: $userId,
            auditable: $event,
            newValues: [
                'status' => $event->status?->value,
                'is_public' => $event->is_public,
                'published_at' => $event->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'event_publication_status',
            ],
        );

        return $event->fresh(Event::RESOURCE_RELATIONS);
    }

    private function resolveTargetStatus(EventStatus $requestedStatus): EventStatus
    {
        if ($requestedStatus !== EventStatus::Published) {
            return $requestedStatus;
        }

        $flow = $this->resolveContentPublicationFlowAction->execute(ContentType::Event);

        return $flow === PublicationFlow::ManualReview
            ? EventStatus::PendingReview
            : EventStatus::Published;
    }
}
