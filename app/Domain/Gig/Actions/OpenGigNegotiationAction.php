<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\OpenGigNegotiationData;
use Domain\Gig\Enums\GigApplicationStatus;
use Domain\Gig\Enums\GigNegotiationEventType;
use Domain\Gig\Enums\GigNegotiationStatus;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Domain\Gig\Models\GigNegotiation;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Poetry\Models\Poem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OpenGigNegotiationAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Gig $gig, GigApplication $application, OpenGigNegotiationData $data): GigNegotiation
    {
        if ((int) $application->gig_id !== (int) $gig->id) {
            throw ValidationException::withMessages([
                'application' => [__('gig.validation.application_not_in_gig')],
            ]);
        }

        if ($gig->type !== GigType::Translator || $gig->giggable_type !== Poem::class) {
            throw ValidationException::withMessages([
                'gig' => [__('gig.validation.negotiation_requires_translation_gig')],
            ]);
        }

        $hasAgreement = $gig->negotiations()
            ->where('status', GigNegotiationStatus::Agreed->value)
            ->exists();

        if (! in_array($gig->status, [GigStatus::Open, GigStatus::Filled], true) || $hasAgreement) {
            throw ValidationException::withMessages([
                'gig' => [__('gig.validation.gig_not_active')],
            ]);
        }

        if (! in_array($application->status, [GigApplicationStatus::Pending, GigApplicationStatus::Accepted], true)) {
            throw ValidationException::withMessages([
                'application' => [__('gig.validation.application_not_negotiable')],
            ]);
        }

        $existing = $gig->negotiations()
            ->where('gig_application_id', $application->id)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'application' => [__('gig.validation.negotiation_already_exists')],
            ]);
        }

        $negotiation = $gig->negotiations()->create([
            'gig_application_id' => $application->id,
            'owner_user_id' => Auth::id(),
            'translator_user_id' => $application->user_id,
            'status' => GigNegotiationStatus::Open,
            'currency' => $data->currency ?? $gig->currency,
        ]);

        $application->status = GigApplicationStatus::Accepted;
        $application->reviewed_by_user_id = Auth::id();
        $application->reviewed_at = now();
        $application->save();

        $gig->status = GigStatus::Filled;
        $gig->updated_by_user_id = Auth::id();
        $gig->save();

        $negotiation->events()->create([
            'author_user_id' => Auth::id(),
            'event_type' => GigNegotiationEventType::OwnerOffer,
            'price' => $data->price ?? $gig->proposed_price,
            'currency' => $data->currency ?? $gig->currency,
            'deadline' => $data->deadline ?? $gig->deadline,
            'note' => $data->note,
        ]);

        audit()->log(
            event: 'gig.negotiation_opened',
            userId: Auth::id(),
            auditable: $negotiation,
            newValues: [
                'gig_id' => $gig->id,
                'gig_application_id' => $application->id,
                'translator_user_id' => $application->user_id,
                'status' => $negotiation->status?->value,
            ],
            metadata: [
                'source' => 'gig_negotiation_open',
            ],
        );

        $this->createNotificationAction->execute(
            userId: $application->user_id,
            type: 'gig.negotiation_opened',
            title: fn () => __('gig.notifications.negotiation_opened_title'),
            message: fn () => __('gig.notifications.negotiation_opened_message'),
            data: [
                'gig_id' => $gig->id,
                'negotiation_id' => $negotiation->id,
            ],
        );

        return $negotiation->fresh(['events.author.profile', 'application.user.profile']);
    }
}
