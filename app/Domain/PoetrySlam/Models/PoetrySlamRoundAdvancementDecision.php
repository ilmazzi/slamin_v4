<?php

namespace Domain\PoetrySlam\Models;

use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementDecisionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoetrySlamRoundAdvancementDecision extends Model
{
    protected $fillable = [
        'poetry_slam_round_id',
        'poetry_slam_entry_id',
        'decision_status',
        'note',
        'decided_at',
    ];

    protected $casts = [
        'decision_status' => PoetrySlamRoundAdvancementDecisionStatus::class,
        'decided_at' => 'datetime',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(PoetrySlamRound::class, 'poetry_slam_round_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(PoetrySlamEntry::class, 'poetry_slam_entry_id');
    }
}
