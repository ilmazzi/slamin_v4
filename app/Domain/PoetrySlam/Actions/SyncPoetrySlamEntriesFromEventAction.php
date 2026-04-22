<?php

namespace Domain\PoetrySlam\Actions;

use Domain\Event\Models\EventParticipantInvite;
use Domain\PoetrySlam\Enums\PoetrySlamEntrySource;
use Domain\PoetrySlam\Enums\PoetrySlamEntryStatus;
use Domain\PoetrySlam\Models\PoetrySlam;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Illuminate\Support\Collection;

class SyncPoetrySlamEntriesFromEventAction
{
    public function execute(PoetrySlam $poetrySlam): Collection
    {
        $poetrySlam->loadMissing([
            'event.participantInvites.user',
            'entries',
        ]);

        $createdEntries = collect();

        /** @var EventParticipantInvite $invite */
        foreach ($poetrySlam->event->participantInvites as $invite) {
            $existingByInvite = $poetrySlam->entries
                ->firstWhere('event_participant_invite_id', $invite->id);

            if ($existingByInvite !== null) {
                continue;
            }

            if ($invite->user_id !== null) {
                $existingByUser = $poetrySlam->entries
                    ->firstWhere('user_id', $invite->user_id);

                if ($existingByUser !== null) {
                    continue;
                }
            }

            $displayName = $invite->user?->profile?->display_name
                ?? $invite->user?->profile?->nickname
                ?? $invite->name
                ?? $invite->email
                ?? 'Participant';

            $entry = PoetrySlamEntry::query()->create([
                'poetry_slam_id' => $poetrySlam->id,
                'entry_source' => PoetrySlamEntrySource::EventParticipant,
                'event_participant_invite_id' => $invite->id,
                'user_id' => $invite->user_id,
                'display_name' => $displayName,
                'email' => $invite->email,
                'status' => PoetrySlamEntryStatus::Registered,
            ]);

            $createdEntries->push($entry);
        }

        return $createdEntries;
    }
}
