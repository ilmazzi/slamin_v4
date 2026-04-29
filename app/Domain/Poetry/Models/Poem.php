<?php

namespace Domain\Poetry\Models;

use Domain\Comment\Models\Comment;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Report\Models\Report;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Domain\Engagement\Models\Like;
use Domain\Engagement\Models\Bookmark;
use Domain\Poetry\Models\PoemCategory;
use Domain\Poetry\Models\PoemType;
use Domain\Moderation\Models\ModerationDecision;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\TranslationJob;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'poem_category_id',
        'poem_type_id',
        'title',
        'slug',
        'content',
        'description',
        'image_path',
        'language',
        'status',
        'is_public',
        'published_at',
        'is_featured',
        'featured_rank',
    ];

    protected $casts = [
        'status' => PoemStatus::class,
        'is_public' => 'boolean',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'featured_rank' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PoemCategory::class, 'poem_category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PoemType::class, 'poem_type_id');
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
    public function entries(): HasMany
    {
        return $this->hasMany(PoetrySlamEntry::class);
    }

    public function gigs(): MorphMany
    {
        return $this->morphMany(Gig::class, 'giggable');
    }

    public function translationJobs(): HasMany
    {
        return $this->hasMany(TranslationJob::class);
    }
}
