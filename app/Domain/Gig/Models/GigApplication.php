<?php

namespace Domain\Gig\Models;

use Domain\Gig\Enums\GigApplicationStatus;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GigApplication extends Model
{
    protected $fillable = [
        'gig_id',
        'user_id',
        'message',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'confirmed_at',
        'rejected_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'status' => GigApplicationStatus::class,
        'reviewed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function negotiations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GigNegotiation::class);
    }
}
