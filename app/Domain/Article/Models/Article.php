<?php

namespace Domain\Article\Models;

use Domain\Article\Enums\ArticleStatus;
use Domain\Comment\Models\Comment;
use Domain\Engagement\Models\Bookmark;
use Domain\Engagement\Models\Like;
use Domain\Moderation\Models\ModerationDecision;
use Domain\Report\Models\Report;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'article_category_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'cover_image_path',
        'language',
        'status',
        'is_public',
        'published_at',
    ];

    protected $casts = [
        'status' => ArticleStatus::class,
        'is_public' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    public function moderationDecisions(): MorphMany
    {
        return $this->morphMany(ModerationDecision::class, 'moderatable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
