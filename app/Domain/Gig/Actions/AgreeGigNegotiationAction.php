<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\GigApplicationStatus;
use Domain\Gig\Enums\GigNegotiationEventType;
use Domain\Gig\Enums\GigNegotiationStatus;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\TranslationJobStatus;
use Domain\Gig\Models\GigNegotiation;
use Domain\Gig\Models\GigNegotiationEvent;
use Domain\Gig\Models\TranslationJob;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgreeGigNegotiationAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(GigNegotiation $negotiation): GigNegotiation
    {
        return DB::transaction(function () use ($negotiation): GigNegotiation {
            $negotiation = GigNegotiation::query()->lockForUpdate()->findOrFail($negotiation->getKey());

            if ($negotiation->status !== GigNegotiationStatus::Open) {
                throw ValidationException::withMessages([
                    'negotiation' => [__('gig.validation.negotiation_not_open')],
                ]);
            }

            $latestOffer = $negotiation->events()
                ->whereIn('event_type', [
                    GigNegotiationEventType::OwnerOffer->value,
                    GigNegotiationEventType::TranslatorOffer->value,
                    GigNegotiationEventType::CounterOffer->value,
                ])
                ->latest()
                ->latest('id')
                ->first();

            if ($latestOffer === null) {
                throw ValidationException::withMessages([
                    'negotiation' => [__('gig.validation.negotiation_without_offer')],
                ]);
            }

            return $this->agree($negotiation, $latestOffer);
        });
    }

    private function agree(GigNegotiation $negotiation, GigNegotiationEvent $latestOffer): GigNegotiation
    {
        $negotiation->status = GigNegotiationStatus::Agreed;
        $negotiation->agreed_price = $latestOffer?->price;
        $negotiation->currency = $latestOffer?->currency ?? $negotiation->currency;
        $negotiation->agreed_deadline = $latestOffer?->deadline;
        $negotiation->closed_at = now();
        $negotiation->save();

        $negotiation->events()->create([
            'author_user_id' => Auth::id(),
            'event_type' => GigNegotiationEventType::Agreement,
            'price' => $negotiation->agreed_price,
            'currency' => $negotiation->currency,
            'deadline' => $negotiation->agreed_deadline,
            'note' => null,
        ]);

        $negotiation->application->update([
            'status' => GigApplicationStatus::Confirmed,
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'confirmed_at' => now(),
        ]);

        $gig = $negotiation->gig;
        $gig->filled_quantity = 1;
        $gig->status = GigStatus::Filled;
        $gig->filled_at = $gig->filled_at ?? now();
        $gig->updated_by_user_id = Auth::id();
        $gig->save();

        $gig->negotiations()
            ->where('id', '!=', $negotiation->id)
            ->where('status', GigNegotiationStatus::Open->value)
            ->update([
                'status' => GigNegotiationStatus::Rejected->value,
                'closed_at' => now(),
                'updated_at' => now(),
            ]);

        $gig->applications()
            ->where('id', '!=', $negotiation->gig_application_id)
            ->whereNotIn('status', [GigApplicationStatus::Withdrawn->value, GigApplicationStatus::Confirmed->value])
            ->update([
                'status' => GigApplicationStatus::Rejected->value,
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
                'rejected_at' => now(),
                'updated_at' => now(),
            ]);

        TranslationJob::query()->firstOrCreate(
            ['gig_negotiation_id' => $negotiation->id],
            [
                'gig_id' => $gig->id,
                'poem_id' => $gig->giggable_id,
                'translator_user_id' => $negotiation->translator_user_id,
                'owner_user_id' => $negotiation->owner_user_id,
                'source_language' => $gig->giggable?->language ?? 'unknown',
                'target_language' => $gig->language,
                'agreed_price' => $negotiation->agreed_price,
                'currency' => $negotiation->currency,
                'deadline' => $negotiation->agreed_deadline,
                'status' => TranslationJobStatus::InProgress,
            ],
        );

        audit()->log(
            event: 'gig.negotiation_agreed',
            userId: Auth::id(),
            auditable: $negotiation,
            newValues: [
                'status' => $negotiation->status?->value,
                'agreed_price' => $negotiation->agreed_price,
                'currency' => $negotiation->currency,
            ],
            metadata: [
                'source' => 'gig_negotiation_agree',
                'gig_id' => $gig->id,
            ],
        );

        $targetUserId = (int) Auth::id() === (int) $negotiation->owner_user_id
            ? $negotiation->translator_user_id
            : $negotiation->owner_user_id;

        $this->createNotificationAction->execute(
            userId: $targetUserId,
            type: 'gig.negotiation_agreed',
            title: fn () => __('gig.notifications.negotiation_agreed_title'),
            message: fn () => __('gig.notifications.negotiation_agreed_message'),
            data: [
                'gig_id' => $gig->id,
                'negotiation_id' => $negotiation->id,
            ],
        );

        return $negotiation->fresh(['events.author.profile', 'application.user.profile']);
    }
}
