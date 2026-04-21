<?php

namespace Domain\Moderation\Models;

use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationDecision extends Model
{
    protected $fillable = [
        'moderator_user_id',
        'moderatable_type',
        'moderatable_id',
        'decision',
        'note',
    ];

    protected $casts = [
        'decision' => ModerationDecisionType::class,
    ];

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_user_id');
    }

    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }
}
