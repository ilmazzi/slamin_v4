<?php

namespace Domain\Gig\Models;

use Domain\Gig\Enums\TranslationCommentStatus;
use Domain\Gig\Enums\TranslationCommentTarget;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationComment extends Model
{
    protected $fillable = [
        'translation_job_id',
        'author_user_id',
        'comment_target',
        'target_locator',
        'content',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'comment_target' => TranslationCommentTarget::class,
        'status' => TranslationCommentStatus::class,
        'resolved_at' => 'datetime',
    ];

    public function translationJob(): BelongsTo
    {
        return $this->belongsTo(TranslationJob::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
