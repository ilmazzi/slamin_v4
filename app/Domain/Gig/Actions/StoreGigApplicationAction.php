<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\StoreGigApplicationData;
use Domain\Gig\Enums\GigApplicationStatus;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreGigApplicationAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Gig $gig, User $user, StoreGigApplicationData $data): GigApplication
    {
        if ($gig->status !== GigStatus::Open) {
            throw ValidationException::withMessages([
                'gig' => [__('gig.validation.not_open_for_applications')],
            ]);
        }

        if ($gig->isManagedBy((int) $user->id)) {
            throw ValidationException::withMessages([
                'gig' => [__('gig.validation.cannot_apply_to_own_gig')],
            ]);
        }

        if ($gig->applications()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'application' => [__('gig.validation.already_applied')],
            ]);
        }

        $application = $gig->applications()->create([
            'user_id' => $user->id,
            'message' => $data->message,
            'status' => GigApplicationStatus::Pending,
        ]);

        audit()->log(
            event: 'gig.application_created',
            userId: Auth::id(),
            auditable: $application,
            newValues: [
                'gig_id' => $gig->id,
                'user_id' => $user->id,
                'status' => $application->status?->value,
                'message' => $application->message,
            ],
            metadata: [
                'source' => 'gig_application_store',
                'giggable_type' => $gig->giggable_type,
                'giggable_id' => $gig->giggable_id,
            ],
        );

        $ownerId = $this->resolveOwnerUserId($gig);

        if ($ownerId !== null && $ownerId !== (int) $user->id) {
            $user->loadMissing('profile');
            $applicantName = $user->profile?->display_name ?? $user->profile?->nickname;
            $gigTitle = (string) $gig->giggable?->title;

            $this->createNotificationAction->execute(
                userId: $ownerId,
                type: 'gig.application_received',
                title: fn () => __('gig.notifications.application_received_title'),
                message: fn () => __('gig.notifications.application_received_message', [
                    'name' => $applicantName ?? __('gig.notifications.someone'),
                    'title' => $gigTitle,
                ]),
                data: [
                    'gig_id' => $gig->id,
                    'application_id' => $application->id,
                    'user_id' => $user->id,
                    'applicant_name' => $applicantName,
                    'gig_title' => $gigTitle,
                ],
            );
        }

        return $application->fresh(['user.profile', 'gig']);
    }

    private function resolveOwnerUserId(Gig $gig): ?int
    {
        $parent = $gig->giggable;

        return $parent?->user_id;
    }
}
