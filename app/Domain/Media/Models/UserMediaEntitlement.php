<?php

namespace Domain\Media\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMediaEntitlement extends Model
{
    protected $fillable = [
        'user_id',
        'media_feature_pack_id',
        'granted_by_user_id',
        'source',
        'config',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'config' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function featurePack(): BelongsTo
    {
        return $this->belongsTo(MediaFeaturePack::class, 'media_feature_pack_id');
    }

    public function isActive(): bool
    {
        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
