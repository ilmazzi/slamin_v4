<?php

namespace Domain\Gig\Models;

use Domain\Gig\Enums\GigNegotiationStatus;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GigNegotiation extends Model
{
    protected $fillable = [
        'gig_id',
        'gig_application_id',
        'owner_user_id',
        'translator_user_id',
        'status',
        'agreed_price',
        'currency',
        'agreed_deadline',
        'closed_at',
    ];

    protected $casts = [
        'status' => GigNegotiationStatus::class,
        'agreed_deadline' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(GigApplication::class, 'gig_application_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function translator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'translator_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(GigNegotiationEvent::class);
    }

    public function translationJob(): HasOne
    {
        return $this->hasOne(TranslationJob::class, 'gig_negotiation_id');
    }
}
