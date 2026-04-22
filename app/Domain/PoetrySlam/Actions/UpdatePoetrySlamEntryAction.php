<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\DTOs\UpdatePoetrySlamEntryData;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Illuminate\Validation\ValidationException;

class UpdatePoetrySlamEntryAction
{
    public function execute(PoetrySlamEntry $entry, int $userId, UpdatePoetrySlamEntryData $data): PoetrySlamEntry
    {
        $oldValues = [
            'status' => $entry->status?->value,
            'notes' => $entry->notes,
            'performance_order' => $entry->performance_order,
        ];

        $updates = [];

        if ($data->hasPerformanceOrder && $data->performanceOrder !== null) {
            $duplicateOrder = PoetrySlamEntry::query()
                ->where('poetry_slam_id', $entry->poetry_slam_id)
                ->where('performance_order', $data->performanceOrder)
                ->whereKeyNot($entry->id)
                ->exists();

            if ($duplicateOrder) {
                throw ValidationException::withMessages([
                    'performance_order' => [__('poetry_slam.validation.performance_order_already_assigned')],
                ]);
            }
        }

        if ($data->hasStatus) {
            $updates['status'] = $data->status;
        }

        if ($data->hasNotes) {
            $updates['notes'] = $data->notes;
        }

        if ($data->hasPerformanceOrder) {
            $updates['performance_order'] = $data->performanceOrder;
        }

        if ($updates !== []) {
            $entry->update($updates);
        }

        audit()->log(
            event: 'poetry_slam.entry_updated',
            userId: $userId,
            auditable: $entry,
            oldValues: $oldValues,
            newValues: [
                'status' => $entry->status?->value,
                'notes' => $entry->notes,
                'performance_order' => $entry->performance_order,
            ],
            metadata: [
                'source' => 'poetry_slam_entry_update',
                'event_id' => $entry->poetrySlam?->event_id,
            ],
        );

        return $entry->fresh(['user']);
    }
}
