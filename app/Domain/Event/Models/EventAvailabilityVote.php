<?php

namespace Domain\Event\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAvailabilityVote extends Model
{
    protected $fillable = [
        'event_availability_option_id',
        'user_id',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(EventAvailabilityOption::class, 'event_availability_option_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
