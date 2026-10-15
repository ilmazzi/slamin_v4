<?php

namespace Domain\Gig\Models;

use Domain\Gig\Enums\TranslationJobStatus;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationJob extends Model
{
    protected $fillable = [
        'gig_id',
        'gig_negotiation_id',
        'poem_id',
        'translator_user_id',
        'owner_user_id',
        'source_language',
        'target_language',
        'agreed_price',
        'currency',
        'deadline',
        'translated_content',
        'status',
        'submitted_at',
        'approved_at',
    ];

    protected $casts = [
        'status' => TranslationJobStatus::class,
        'deadline' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(GigNegotiation::class, 'gig_negotiation_id');
    }

    public function poem(): BelongsTo
    {
        return $this->belongsTo(Poem::class);
    }

    public function translator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'translator_user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TranslationComment::class);
    }

    public function involves(int $userId): bool
    {
        return (int) $this->owner_user_id === $userId || (int) $this->translator_user_id === $userId;
    }
}
