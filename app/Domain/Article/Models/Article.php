<?php

namespace Domain\Article\Models;

use Domain\Article\Enums\ArticleStatus;
use Domain\Comment\Models\Comment;
use Domain\ContentTranslation\Concerns\HasContentTranslations;
use Domain\ContentTranslation\Contracts\TranslatableContent;
use Domain\Engagement\Models\Bookmark;
use Domain\Engagement\Models\Like;
use Domain\Group\Enums\GroupContentType;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupContentLink;
use Domain\Moderation\Models\ModerationDecision;
use Domain\Report\Models\Report;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model implements TranslatableContent
{
    use HasContentTranslations;
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
        'is_featured',
        'featured_rank',
    ];

    protected $casts = [
        'status' => ArticleStatus::class,
        'is_public' => 'boolean',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'featured_rank' => 'integer',
    ];

    public function scopeVisibleTo(Builder $query, ?int $viewerId): Builder
    {
        return $query->where(function (Builder $query) use ($viewerId) {
            $query->where(fn (Builder $query) => $query
                ->where('status', ArticleStatus::Published)
                ->where('is_public', true));

            if ($viewerId) {
                $query->orWhere('user_id', $viewerId);
            }
        });
    }

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

    public function groupLinks(): HasMany
    {
        return $this->hasMany(GroupContentLink::class, 'content_id')
            ->where('content_type', GroupContentType::Article);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_content_links', 'content_id', 'group_id')
            ->where('group_content_links.content_type', GroupContentType::Article->value)
            ->where('group_content_links.status', 'accepted')
            ->withTimestamps();
    }
}
