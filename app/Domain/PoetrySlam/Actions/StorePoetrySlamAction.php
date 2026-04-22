<?php

namespace Domain\PoetrySlam\Actions;

use Domain\Event\Models\Event;
use Domain\PoetrySlam\DTOs\StorePoetrySlamData;
use Domain\PoetrySlam\Enums\PoetrySlamStatus;
use Domain\PoetrySlam\Models\PoetrySlam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StorePoetrySlamAction
{
    public function __construct(
        private readonly SyncPoetrySlamEntriesFromEventAction $syncPoetrySlamEntriesFromEventAction,
    ) {
    }

    public function execute(Event $event, StorePoetrySlamData $data): PoetrySlam
    {
        $event->loadMissing('category', 'poetrySlam');

        if ($event->category?->slug !== 'poetry-slam') {
            throw ValidationException::withMessages([
                'event_id' => [__('poetry_slam.validation.event_must_be_poetry_slam')],
            ]);
        }

        if ($event->poetrySlam !== null) {
            throw ValidationException::withMessages([
                'event_id' => [__('poetry_slam.validation.already_exists')],
            ]);
        }

        $poetrySlam = PoetrySlam::query()->create([
            'event_id' => $event->id,
            'status' => PoetrySlamStatus::Draft,
            'rules_summary' => $data->rulesSummary,
            'slots_limit' => $data->slotsLimit,
            'judging_mode' => $data->judgingMode,
            'settings' => $data->settings,
        ]);

        $createdEntries = $this->syncPoetrySlamEntriesFromEventAction->execute($poetrySlam);

        audit()->log(
            event: 'poetry_slam.created',
            userId: Auth::id(),
            auditable: $poetrySlam,
            newValues: [
                'event_id' => $poetrySlam->event_id,
                'status' => $poetrySlam->status?->value,
                'rules_summary' => $poetrySlam->rules_summary,
                'slots_limit' => $poetrySlam->slots_limit,
                'judging_mode' => $poetrySlam->judging_mode,
                'settings' => $poetrySlam->settings,
                'imported_entries_count' => $createdEntries->count(),
            ],
            metadata: [
                'source' => 'poetry_slam_store',
                'event_id' => $event->id,
            ],
        );

        return $poetrySlam->fresh([
            'event.category',
            'entries',
        ]);
    }
}