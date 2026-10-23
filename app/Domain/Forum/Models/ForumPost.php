<?php

namespace Domain\Forum\Models;

use Domain\Comment\Models\Comment;
use Domain\Engagement\Models\Vote;
use Domain\Forum\Enums\ForumPostStatus;
use Domain\Report\Models\Report;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ForumPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'public_id',
        'user_id',
        'forum_category_id',
        'title',
        'slug',
        'body',
        'status',
        'is_locked',
        'is_pinned',
        'score',
        'comments_count',
    ];

    protected $casts = [
        'status' => ForumPostStatus::class,
        'is_locked' => 'boolean',
        'is_pinned' => 'boolean',
        'score' => 'integer',
        'comments_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $post) {
            if (empty($post->public_id)) {
                $post->public_id = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function isPublished(): bool
    {
        return $this->status === ForumPostStatus::Published;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isPublished() && $this->category?->is_active === true;
    }
}
