<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\Enums\PoetrySlamEntrySource;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Illuminate\Validation\ValidationException;

class DeletePoetrySlamEntryAction
{
    public function execute(PoetrySlamEntry $entry, int $userId): void
    {
        if ($entry->entry_source === PoetrySlamEntrySource::EventParticipant) {
            throw ValidationException::withMessages([
                'entry' => [__('poetry_slam.validation.event_participant_entry_cannot_be_deleted')],
            ]);
        }

        $oldValues = [
            'poetry_slam_id' => $entry->poetry_slam_id,
            'entry_source' => $entry->entry_source?->value,
            'event_participant_invite_id' => $entry->event_participant_invite_id,
            'user_id' => $entry->user_id,
            'display_name' => $entry->display_name,
            'email' => $entry->email,
            'notes' => $entry->notes,
            'status' => $entry->status?->value,
        ];

        $entry->delete();

        audit()->log(
            event: 'poetry_slam.entry_deleted',
            userId: $userId,
            auditable: $entry,
            oldValues: $oldValues,
            metadata: [
                'source' => 'poetry_slam_entry_delete',
                'event_id' => $entry->poetrySlam?->event_id,
            ],
        );
    }
}
