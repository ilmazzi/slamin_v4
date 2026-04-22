<?php

namespace Domain\PoetrySlam\Models;

use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementBasedOn;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementMode;
use Domain\PoetrySlam\Enums\PoetrySlamRoundScoringType;
use Domain\PoetrySlam\Enums\PoetrySlamRoundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoetrySlamRound extends Model
{
    protected $fillable = [
        'poetry_slam_id',
        'round_number',
        'name',
        'judges_count',
        'scoring_type',
        'advancement_mode',
        'advancement_value',
        'advancement_based_on',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'round_number' => 'integer',
        'judges_count' => 'integer',
        'scoring_type' => PoetrySlamRoundScoringType::class,
        'advancement_mode' => PoetrySlamRoundAdvancementMode::class,
        'advancement_value' => 'integer',
        'advancement_based_on' => PoetrySlamRoundAdvancementBasedOn::class,
        'status' => PoetrySlamRoundStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function poetrySlam(): BelongsTo
    {
        return $this->belongsTo(PoetrySlam::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PoetrySlamScore::class, 'poetry_slam_round_id');
    }

    public function advancementDecisions(): HasMany
    {
        return $this->hasMany(PoetrySlamRoundAdvancementDecision::class, 'poetry_slam_round_id');
    }

    public function effectiveScoringType(): PoetrySlamRoundScoringType
    {
        if ($this->scoring_type === PoetrySlamRoundScoringType::TrimmedSum && $this->judges_count < 3) {
            return PoetrySlamRoundScoringType::Sum;
        }

        return $this->scoring_type;
    }

    public function usesManualAdvancement(): bool
    {
        return $this->advancement_mode === PoetrySlamRoundAdvancementMode::ManualSelection;
    }
}
