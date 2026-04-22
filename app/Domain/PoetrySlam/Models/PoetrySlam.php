<?php

namespace Domain\PoetrySlam\Models;

use Domain\Event\Models\Event;
use Domain\PoetrySlam\Enums\PoetrySlamStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoetrySlam extends Model
{
    protected $fillable = [
        'event_id',
        'status',
        'rules_summary',
        'slots_limit',
        'judging_mode',
        'settings',
    ];

    protected $casts = [
        'status' => PoetrySlamStatus::class,
        'slots_limit' => 'integer',
        'settings' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
    public function entries(): HasMany
    {
        return $this->hasMany(PoetrySlamEntry::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(PoetrySlamRound::class);
    }
}
