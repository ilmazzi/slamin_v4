<?php

namespace Domain\Event\Models;

use Domain\Group\Models\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventGroupLink extends Model
{
    protected $table = 'event_group';

    protected $fillable = [
        'event_id',
        'group_id',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
