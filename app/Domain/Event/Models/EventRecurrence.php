<?php

namespace Domain\Event\Models;

use Domain\Event\Enums\EventRecurrenceFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRecurrence extends Model
{
    protected $fillable = [
        'event_id',
        'frequency',
        'interval',
        'occurrences_count',
        'ends_at',
    ];

    protected $casts = [
        'frequency' => EventRecurrenceFrequency::class,
        'ends_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
