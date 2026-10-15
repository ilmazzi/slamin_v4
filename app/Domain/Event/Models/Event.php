<?php

namespace Domain\Event\Models;

use Domain\Comment\Models\Comment;
use Domain\Engagement\Models\Bookmark;
use Domain\Engagement\Models\Follow;
use Domain\Engagement\Models\Like;
use Domain\Event\Enums\EventDeliveryMode;
use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Enums\EventStatus;
use Domain\Gig\Models\Gig;
use Domain\Group\Models\Group;
use Domain\Moderation\Models\ModerationDecision;
use Domain\PoetrySlam\Models\PoetrySlam;
use Domain\Report\Models\Report;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    /**
     * Relations read by EventResource.
     */
    public const RESOURCE_RELATIONS = [
        'user.profile',
        'category.translations',
        'festival',
        'occurrences',
        'recurrence',
        'availabilityPoll',
        'availabilityOptions.votes',
        'venueDetail',
        'onlineDetail',
        'groupLinks',
        'participantInvites',
        'audienceInvites.user.profile',
    ];

    protected $fillable = [
        'user_id',
        'event_category_id',
        'festival_event_id',
        'title',
        'subtitle',
        'slug',
        'description',
        'requirements',
        'schedule_mode',
        'delivery_mode',
        'cover_image_path',
        'has_cost',
        'price',
        'currency',
        'promo_video_url',
        'registration_deadline',
        'public_invite_enabled',
        'audience_capacity_enabled',
        'audience_capacity',
        'language',
        'status',
        'is_public',
        'published_at',
    ];

    protected $casts = [
        'schedule_mode' => EventScheduleMode::class,
        'delivery_mode' => EventDeliveryMode::class,
        'has_cost' => 'boolean',
        'registration_deadline' => 'datetime',
        'public_invite_enabled' => 'boolean',
        'audience_capacity_enabled' => 'boolean',
        'status' => EventStatus::class,
        'is_public' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function festival(): BelongsTo
    {
        return $this->belongsTo(self::class, 'festival_event_id');
    }

    public function childFestivalEvents(): HasMany
    {
        return $this->hasMany(self::class, 'festival_event_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(EventOccurrence::class);
    }

    public function recurrence(): HasOne
    {
        return $this->hasOne(EventRecurrence::class);
    }

    public function availabilityPoll(): HasOne
    {
        return $this->hasOne(EventAvailabilityPoll::class);
    }

    public function availabilityOptions(): HasMany
    {
        return $this->hasMany(EventAvailabilityOption::class);
    }

    public function availabilityVotes(): HasManyThrough
    {
        return $this->hasManyThrough(
            EventAvailabilityVote::class,
            EventAvailabilityOption::class,
            'event_id',
            'event_availability_option_id'
        );
    }

    public function venueDetail(): HasOne
    {
        return $this->hasOne(EventVenueDetail::class);
    }

    public function onlineDetail(): HasOne
    {
        return $this->hasOne(EventOnlineDetail::class);
    }

    public function participantInvites(): HasMany
    {
        return $this->hasMany(EventParticipantInvite::class);
    }

    public function audienceInvites(): HasMany
    {
        return $this->hasMany(EventAudienceInvite::class);
    }

    public function groupLinks(): HasMany
    {
        return $this->hasMany(EventGroupLink::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'event_group')
            ->withTimestamps();
    }

    public function poetrySlam(): HasOne
    {
        return $this->hasOne(PoetrySlam::class);
    }

    public function gigs(): MorphMany
    {
        return $this->morphMany(Gig::class, 'giggable');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    public function followers(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function moderationDecisions(): MorphMany
    {
        return $this->morphMany(ModerationDecision::class, 'moderatable');
    }

    public function scopeWithEngagement(Builder $query, ?int $userId): Builder
    {
        return $query
            ->withCount(['likes', 'comments', 'bookmarks'])
            ->withExists([
                'likes as is_liked' => fn ($builder) => $builder->where('user_id', $userId ?? 0),
                'bookmarks as is_bookmarked' => fn ($builder) => $builder->where('user_id', $userId ?? 0),
            ]);
    }

    public function loadEngagement(?int $userId): static
    {
        $this->loadCount(['likes', 'comments', 'bookmarks']);
        $this->loadExists([
            'likes as is_liked' => fn ($builder) => $builder->where('user_id', $userId ?? 0),
            'bookmarks as is_bookmarked' => fn ($builder) => $builder->where('user_id', $userId ?? 0),
        ]);

        return $this;
    }

    public function scopeWithFollowState(Builder $query, ?int $userId): Builder
    {
        return $query
            ->withCount('followers')
            ->withExists(['followers as is_following' => fn ($builder) => $builder->where('user_id', $userId ?? 0)]);
    }
}
