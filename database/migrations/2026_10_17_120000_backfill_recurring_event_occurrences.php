<?php

use Domain\Event\Actions\SyncEventOccurrencesAction;
use Domain\Event\Models\Event;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $syncOccurrences = app(SyncEventOccurrencesAction::class);

        Event::query()
            ->where('schedule_mode', 'recurring')
            ->with(['occurrences', 'recurrence'])
            ->chunkById(100, function ($events) use ($syncOccurrences): void {
                foreach ($events as $event) {
                    /** @var Event $event */
                    $primary = $event->occurrences->firstWhere('is_primary', true)
                        ?? $event->occurrences->first();
                    $recurrence = $event->recurrence;

                    if (! $primary || ! $recurrence) {
                        continue;
                    }

                    $syncOccurrences->execute(
                        $event,
                        [
                            'starts_at' => $primary->starts_at->toISOString(),
                            'ends_at' => $primary->ends_at?->toISOString(),
                            'timezone' => $primary->timezone,
                        ],
                        [
                            'frequency' => $recurrence->frequency->value,
                            'interval' => $recurrence->interval,
                            'occurrences_count' => $recurrence->occurrences_count,
                            'ends_at' => $recurrence->ends_at?->toISOString(),
                        ],
                    );
                }
            });
    }

    public function down(): void
    {
        // Generated occurrences are valid event data and are intentionally retained.
    }
};
