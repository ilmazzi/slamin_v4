<?php

namespace Domain\PoetrySlam\Models;

use Domain\Event\Models\EventParticipantInvite;
use Domain\PoetrySlam\Enums\PoetrySlamEntrySource;
use Domain\PoetrySlam\Enums\PoetrySlamEntryStatus;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoetrySlamEntry extends Model
{
    protected $fillable = [
        'poetry_slam_id',
        'entry_source',
        'event_participant_invite_id',
        'user_id',
        'display_name',
        'email',
        'notes',
        'status',
        'performance_order',
    ];

    protected $casts = [
        'entry_source' => PoetrySlamEntrySource::class,
        'status' => PoetrySlamEntryStatus::class,
        'performance_order' => 'integer',
    ];

    public function poetrySlam(): BelongsTo
    {
        return $this->belongsTo(PoetrySlam::class);
    }

    public function eventParticipantInvite(): BelongsTo
    {
        return $this->belongsTo(EventParticipantInvite::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}