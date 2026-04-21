<?php

namespace Domain\Event\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAvailabilityPoll extends Model
{
    protected $fillable = [
        'event_id',
        'response_deadline',
        'instructions',
    ];

    protected $casts = [
        'response_deadline' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
