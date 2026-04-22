<?php

namespace Domain\Media\Models;

use Domain\Media\Enums\MediaCheckoutOrderStatus;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaCheckoutOrder extends Model
{
    protected $fillable = [
        'user_id',
        'media_feature_pack_id',
        'status',
        'provider',
        'provider_checkout_session_id',
        'provider_payment_intent_id',
        'amount',
        'currency',
        'feature_code',
        'feature_name',
        'feature_config',
        'payload',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
            'status' => MediaCheckoutOrderStatus::class,
        'feature_config' => 'array',
        'payload' => 'array',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function featurePack(): BelongsTo
    {
        return $this->belongsTo(MediaFeaturePack::class, 'media_feature_pack_id');
    }
}
