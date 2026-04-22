<?php

namespace Domain\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFeaturePack extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'config',
        'is_active',
        'price_amount',
        'currency',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public function entitlements(): HasMany
    {
        return $this->hasMany(UserMediaEntitlement::class);
    }
}
