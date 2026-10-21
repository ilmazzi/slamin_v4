<?php

namespace Domain\Event\Actions;

use Domain\Admin\Actions\ResolveContentPublicationFlowAction;
use Domain\Event\DTOs\StoreEventData;
use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Group\Actions\ProposeEventToGroupsAction;
use Domain\Shared\Enums\ContentType;
use Domain\Shared\Enums\PublicationFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreEventAction
{
    public function __construct(
        private readonly ResolveContentPublicationFlowAction $resolveContentPublicationFlowAction,
        private readonly UpsertRecentEventPlaceAction $upsertRecentEventPlaceAction,
        private readonly BuildEventInviteNotificationsAction $buildEventInviteNotificationsAction,
        private readonly NotifyEventInvitesAction $notifyEventInvitesAction,
        private readonly SyncEventOccurrencesAction $syncEventOccurrencesAction,
        private readonly ProposeEventToGroupsAction $proposeEventToGroupsAction,
        private readonly SyncEventCollaboratorsAction $syncEventCollaboratorsAction,
    ) {}

    public function execute(StoreEventData $data): Event
    {
        return DB::transaction(function () use ($data) {
            $coverImagePath = $data->coverImage?->store('events', 'public');
            $status = $this->resolveTargetStatus($data->status);
            $publishedAt = $status === EventStatus::Published
                ? ($data->publishedAt ? now()->parse($data->publishedAt) : now())
                : null;

            $event = Event::query()->create([
                'user_id' => $data->userId,
                'event_category_id' => $data->eventCategoryId,
                'festival_event_id' => $data->festivalEventId,
                'title' => $data->title,
                'subtitle' => $data->subtitle,
                'slug' => $this->generateUniqueSlug($data->title),
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

            $this->syncRelatedData($event, $data);
            $notifyPayload = $this->buildEventInviteNotificationsAction->execute($event, $data);

            audit()->log(
                event: 'event.created',
                userId: $data->userId,
                auditable: $event,
                newValues: [
                    'title' => $event->title,
                    'slug' => $event->slug,
                    'event_category_id' => $event->event_category_id,
                    'festival_event_id' => $event->festival_event_id,
                    'schedule_mode' => $event->schedule_mode?->value,
                    'delivery_mode' => $event->delivery_mode?->value,
                    'status' => $event->status?->value,
                    'is_public' => $event->is_public,
                    'published_at' => $event->published_at?->toISOString(),
                ],
                metadata: [
                    'source' => 'event_store',
                ],
            );

            $this->notifyEventInvitesAction->execute(
                $event,
                $notifyPayload['participantInvites'],
                $notifyPayload['externalParticipantInvites'],
                $notifyPayload['audienceInvites'],
                $notifyPayload['externalAudienceInvites'],
            );

            return $this->loadFullEvent($event);
        });
    }

    private function syncRelatedData(Event $event, StoreEventData $data): void
    {
        $this->syncEventOccurrencesAction->execute(
            $event,
            $data->occurrence,
            $data->scheduleMode === EventScheduleMode::Recurring ? $data->recurrence : null,
        );

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
            $this->proposeEventToGroupsAction->execute($event, $data->groupIds, $data->userId);
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

        $this->syncEventCollaboratorsAction->execute($event, $data->collaboratorUserIds);

        if ($data->gigs !== []) {
            $now = now();

            $event->gigs()->createMany(
                collect($data->gigs)->map(fn ($gig) => [
                    'type' => $gig['type'],
                    'quantity' => $gig['quantity'],
                    'filled_quantity' => 0,
                    'language' => $gig['language'] ?? null,
                    'hospitality_required' => (bool) ($gig['hospitality_required'] ?? false),
                    'description' => $gig['description'] ?? null,
                    'proposed_price' => ! empty($gig['cachet_enabled']) ? ($gig['proposed_price'] ?? null) : null,
                    'currency' => (! empty($gig['cachet_enabled']) || ! empty($gig['travel_expenses_enabled'])) ? ($gig['currency'] ?? null) : null,
                    'deadline' => $gig['deadline'] ?? null,
                    'special_requirements' => $gig['special_requirements'] ?? null,
                    'cachet_enabled' => (bool) ($gig['cachet_enabled'] ?? false),
                    'travel_expenses_enabled' => (bool) ($gig['travel_expenses_enabled'] ?? false),
                    'travel_expenses_max' => ! empty($gig['travel_expenses_enabled']) ? ($gig['travel_expenses_max'] ?? null) : null,
                    'accommodation_enabled' => (bool) ($gig['accommodation_enabled'] ?? false),
                    'accommodation_details' => ! empty($gig['accommodation_enabled']) ? ($gig['accommodation_details'] ?? null) : null,
                    'status' => $gig['status'] ?? 'open',
                    'opened_at' => ($gig['status'] ?? 'open') === 'open' ? $now : null,
                    'closed_at' => in_array(($gig['status'] ?? 'open'), ['closed', 'cancelled'], true) ? $now : null,
                    'created_by_user_id' => $data->userId,
                    'updated_by_user_id' => $data->userId,
                ])->all()
            );
        }
    }

    private function loadFullEvent(Event $event): Event
    {
        return $event->load(Event::RESOURCE_RELATIONS);
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

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'event';
        $originalSlug = $slug;
        $counter = 2;

        while (Event::query()->where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
