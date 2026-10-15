<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\UpdateGigData;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateEventGigAction
{
    public function execute(Gig $gig, UpdateGigData $data): Gig
    {
        $oldValues = [
            'type' => $gig->type?->value,
            'quantity' => $gig->quantity,
            'language' => $gig->language,
            'hospitality_required' => $gig->hospitality_required,
            'description' => $gig->description,
            'status' => $gig->status?->value,
            'opened_at' => $gig->opened_at?->toISOString(),
            'closed_at' => $gig->closed_at?->toISOString(),
        ];

        if ($data->type !== null) {
            $gig->type = $data->type;
        }

        if ($data->quantity !== null) {
            if ($data->quantity < (int) $gig->filled_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => [__('gig.validation.quantity_below_filled')],
                ]);
            }

            $gig->quantity = $data->quantity;
        }

        if ($data->offerAttributes !== []) {
            $gig->fill($data->offerAttributes);

            if (! $gig->cachet_enabled) {
                $gig->proposed_price = null;
            }

            if (! $gig->travel_expenses_enabled) {
                $gig->travel_expenses_max = null;
            }

            if (! $gig->accommodation_enabled) {
                $gig->accommodation_details = null;
            }

            if (! $gig->cachet_enabled && ! $gig->travel_expenses_enabled) {
                $gig->currency = null;
            }
        }

        if ($data->languageProvided) {
            $gig->language = $data->language;
        }

        if ($data->hospitalityRequired !== null) {
            $gig->hospitality_required = $data->hospitalityRequired;
        }

        if ($data->descriptionProvided) {
            $gig->description = $data->description;
        }

        if ($data->status !== null) {
            $this->applyStatusTransition($gig, $data->status);
        }

        if ($gig->status === GigStatus::Open && (int) $gig->filled_quantity >= (int) $gig->quantity) {
            $gig->status = GigStatus::Filled;
            $gig->filled_at = $gig->filled_at ?? now();
        } elseif ($gig->status === GigStatus::Filled && (int) $gig->filled_quantity < (int) $gig->quantity) {
            $gig->status = GigStatus::Open;
            $gig->filled_at = null;
        }

        $gig->updated_by_user_id = Auth::id();
        $gig->save();

        audit()->log(
            event: 'gig.updated',
            userId: Auth::id(),
            auditable: $gig,
            oldValues: $oldValues,
            newValues: [
                'type' => $gig->type?->value,
                'quantity' => $gig->quantity,
                'language' => $gig->language,
                'hospitality_required' => $gig->hospitality_required,
                'description' => $gig->description,
                'status' => $gig->status?->value,
                'opened_at' => $gig->opened_at?->toISOString(),
                'closed_at' => $gig->closed_at?->toISOString(),
            ],
            metadata: [
                'source' => 'event_gig_update',
                'event_id' => $gig->giggable_id,
            ],
        );

        return $gig->fresh();
    }

    private function applyStatusTransition(Gig $gig, GigStatus $status): void
    {
        $gig->status = $status;

        if ($status === GigStatus::Open && $gig->opened_at === null) {
            $gig->opened_at = now();
            $gig->closed_at = null;
        }

        if (in_array($status, [GigStatus::Closed, GigStatus::Cancelled], true)) {
            $gig->closed_at = now();
        }
    }
}
