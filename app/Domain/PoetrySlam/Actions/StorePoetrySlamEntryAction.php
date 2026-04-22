<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\DTOs\StorePoetrySlamEntryData;
use Domain\PoetrySlam\Enums\PoetrySlamEntrySource;
use Domain\PoetrySlam\Enums\PoetrySlamEntryStatus;
use Domain\PoetrySlam\Models\PoetrySlam;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StorePoetrySlamEntryAction
{
    public function execute(PoetrySlam $poetrySlam, StorePoetrySlamEntryData $data): PoetrySlamEntry
    {
        $poetrySlam->loadMissing('entries');

        if ($poetrySlam->slots_limit !== null && $poetrySlam->entries->count() >= $poetrySlam->slots_limit) {
            throw ValidationException::withMessages([
                'entry' => [__('poetry_slam.validation.slots_limit_reached')],
            ]);
        }

        $entry = match ($data->entrySource) {
            PoetrySlamEntrySource::SiteUser => $this->createSiteUserEntry($poetrySlam, $data),
            PoetrySlamEntrySource::External => $this->createExternalEntry($poetrySlam, $data),
            PoetrySlamEntrySource::EventParticipant => throw ValidationException::withMessages([
                'entry_source' => [__('poetry_slam.validation.event_participant_entry_not_manually_creatable')],
            ]),
        };

        audit()->log(
            event: 'poetry_slam.entry_created',
            userId: Auth::id(),
            auditable: $entry,
            newValues: [
                'poetry_slam_id' => $entry->poetry_slam_id,
                'entry_source' => $entry->entry_source?->value,
                'user_id' => $entry->user_id,
                'display_name' => $entry->display_name,
                'email' => $entry->email,
                'notes' => $entry->notes,
                'status' => $entry->status?->value,
            ],
            metadata: [
                'source' => 'poetry_slam_entry_store',
                'event_id' => $poetrySlam->event_id,
            ],
        );

        return $entry->fresh(['user']);
    }

    private function createSiteUserEntry(PoetrySlam $poetrySlam, StorePoetrySlamEntryData $data): PoetrySlamEntry
    {
        $existingEntry = $poetrySlam->entries->firstWhere('user_id', $data->userId);

        if ($existingEntry !== null) {
            throw ValidationException::withMessages([
                'user_id' => [__('poetry_slam.validation.user_already_registered')],
            ]);
        }

        $user = User::query()->with('profile')->findOrFail($data->userId);

        return PoetrySlamEntry::query()->create([
            'poetry_slam_id' => $poetrySlam->id,
            'entry_source' => PoetrySlamEntrySource::SiteUser,
            'user_id' => $user->id,
            'display_name' => $user->profile?->display_name
                ?? $user->profile?->nickname
                ?? $user->email,
            'email' => $user->email,
            'notes' => $data->notes,
            'status' => PoetrySlamEntryStatus::Registered,
        ]);
    }

    private function createExternalEntry(PoetrySlam $poetrySlam, StorePoetrySlamEntryData $data): PoetrySlamEntry
    {
        $normalizedEmail = $data->email !== null ? mb_strtolower($data->email) : null;
        $normalizedDisplayName = mb_strtolower(trim((string) $data->displayName));

        $duplicateEntry = $poetrySlam->entries->first(function (PoetrySlamEntry $entry) use ($normalizedDisplayName, $normalizedEmail): bool {
            $entryEmail = $entry->email !== null ? mb_strtolower($entry->email) : null;
            $entryDisplayName = mb_strtolower(trim($entry->display_name));

            if ($normalizedEmail !== null && $entryEmail === $normalizedEmail) {
                return true;
            }

            return $entry->user_id === null && $entryDisplayName === $normalizedDisplayName;
        });

        if ($duplicateEntry !== null) {
            throw ValidationException::withMessages([
                'display_name' => [__('poetry_slam.validation.external_participant_already_registered')],
            ]);
        }

        return PoetrySlamEntry::query()->create([
            'poetry_slam_id' => $poetrySlam->id,
            'entry_source' => PoetrySlamEntrySource::External,
            'display_name' => $data->displayName,
            'email' => $normalizedEmail,
            'notes' => $data->notes,
            'status' => PoetrySlamEntryStatus::Registered,
        ]);
    }
}
