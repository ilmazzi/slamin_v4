<?php

namespace Domain\Event\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCategoryTranslation extends Model
{
    protected $fillable = [
        'event_category_id',
        'locale',
        'name',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }
}
