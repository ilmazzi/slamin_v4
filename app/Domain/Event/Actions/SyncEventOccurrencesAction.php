<?php

namespace Domain\Event\Actions;

use Carbon\CarbonImmutable;
use Domain\Event\Enums\EventRecurrenceFrequency;
use Domain\Event\Models\Event;

class SyncEventOccurrencesAction
{
    public function execute(Event $event, ?array $occurrence, ?array $recurrence = null): void
    {
        $event->occurrences()->delete();

        if (! $occurrence || empty($occurrence['starts_at'])) {
            return;
        }

        $timezone = $occurrence['timezone'] ?? 'UTC';
        $startsAt = $this->parseInTimezone($occurrence['starts_at'], $timezone);
        $endsAt = ! empty($occurrence['ends_at'])
            ? $this->parseInTimezone($occurrence['ends_at'], $timezone)
            : null;
        $durationInSeconds = $endsAt?->diffInSeconds($startsAt, true);

        $event->occurrences()->create([
            'starts_at' => $startsAt->utc(),
            'ends_at' => $endsAt?->utc(),
            'timezone' => $timezone,
            'is_primary' => true,
        ]);

        if (! $recurrence) {
            return;
        }

        $frequency = EventRecurrenceFrequency::from($recurrence['frequency']);
        $interval = max(1, (int) ($recurrence['interval'] ?? 1));
        $occurrencesCount = max(1, (int) ($recurrence['occurrences_count'] ?? 1));
        $recurrenceEndsAt = ! empty($recurrence['ends_at'])
            ? $this->parseInTimezone($recurrence['ends_at'], $timezone)
            : null;

        for ($index = 1; $index < $occurrencesCount; $index++) {
            $nextStartsAt = $this->addInterval($startsAt, $frequency, $interval * $index);

            if ($recurrenceEndsAt && $nextStartsAt->isAfter($recurrenceEndsAt)) {
                break;
            }

            $event->occurrences()->create([
                'starts_at' => $nextStartsAt->utc(),
                'ends_at' => $durationInSeconds !== null
                    ? $nextStartsAt->addSeconds($durationInSeconds)->utc()
                    : null,
                'timezone' => $timezone,
                'is_primary' => false,
            ]);
        }
    }

    private function addInterval(
        CarbonImmutable $startsAt,
        EventRecurrenceFrequency $frequency,
        int $amount,
    ): CarbonImmutable {
        return match ($frequency) {
            EventRecurrenceFrequency::Daily => $startsAt->addDays($amount),
            EventRecurrenceFrequency::Weekly => $startsAt->addWeeks($amount),
            EventRecurrenceFrequency::Monthly => $startsAt->addMonthsNoOverflow($amount),
            EventRecurrenceFrequency::Yearly => $startsAt->addYearsNoOverflow($amount),
        };
    }

    private function parseInTimezone(string $value, string $timezone): CarbonImmutable
    {
        $hasExplicitTimezone = preg_match('/(?:Z|[+-]\d{2}:?\d{2})$/i', $value) === 1;

        return $hasExplicitTimezone
            ? CarbonImmutable::parse($value)->setTimezone($timezone)
            : CarbonImmutable::parse($value, $timezone);
    }
}
