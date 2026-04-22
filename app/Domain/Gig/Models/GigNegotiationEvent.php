<?php

namespace Domain\Gig\Models;

use Domain\Gig\Enums\GigNegotiationEventType;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GigNegotiationEvent extends Model
{
    protected $fillable = [
        'gig_negotiation_id',
        'author_user_id',
        'event_type',
        'price',
        'currency',
        'deadline',
        'note',
    ];

    protected $casts = [
        'event_type' => GigNegotiationEventType::class,
        'deadline' => 'datetime',
    ];

    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(GigNegotiation::class, 'gig_negotiation_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
