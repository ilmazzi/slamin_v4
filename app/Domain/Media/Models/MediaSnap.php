<?php

namespace Domain\Media\Models;

use Domain\Media\Enums\MediaSnapKind;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaSnap extends Model
{
    protected $fillable = [
        'media_item_id',
        'user_id',
        'timestamp_seconds',
        'kind',
        'comment',
    ];

    protected $casts = [
        'kind' => MediaSnapKind::class,
    ];

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'media_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
