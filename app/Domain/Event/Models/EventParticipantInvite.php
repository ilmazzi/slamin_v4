<?php

namespace Domain\Event\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipantInvite extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'name',
        'email',
        'role',
        'invite_type',
        'status',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
