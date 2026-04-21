<?php

namespace Domain\Event\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventOnlineDetail extends Model
{
    protected $fillable = [
        'event_id',
        'meeting_url',
        'timezone',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
