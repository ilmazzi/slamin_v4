<?php

namespace Domain\PoetrySlam\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoetrySlamScore extends Model
{
    protected $fillable = [
        'poetry_slam_round_id',
        'poetry_slam_entry_id',
        'judge_number',
        'score',
    ];

    protected $casts = [
        'judge_number' => 'integer',
        'score' => 'decimal:2',
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
