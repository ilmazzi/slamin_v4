<?php

namespace Domain\Event\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventVenueDetail extends Model
{
    protected $fillable = [
        'event_id',
        'place_name',
        'country_code',
        'city',
        'postal_code',
        'address_line',
        'latitude',
        'longitude',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
