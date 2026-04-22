<?php

namespace Domain\Event\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventAvailabilityOption extends Model
{
    protected $fillable = [
        'event_id',
        'starts_at',
        'ends_at',
        'timezone',
        'description',
        'finalized_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(EventAvailabilityVote::class, 'event_availability_option_id');
    }
}
