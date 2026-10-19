<?php

namespace Domain\Group\Models;

use Domain\Chat\Models\ChatThread;
use Domain\Event\Models\Event;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'visibility',
        'website_url',
        'facebook_url',
        'instagram_url',
        'youtube_url',
        'group_category_id',
        'is_listed',
        'auto_accept_events',
        'is_active',
    ];

    protected $casts = [
        'visibility' => GroupVisibility::class,
        'is_listed' => 'boolean',
        'auto_accept_events' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GroupCategory::class, 'group_category_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', GroupMemberStatus::Active);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(GroupAnnouncement::class);
    }

    public function contentLinks(): HasMany
    {
        return $this->hasMany(GroupContentLink::class);
    }

    public function parentAffiliations(): HasMany
    {
        return $this->hasMany(GroupAffiliation::class, 'child_group_id');
    }

    public function childAffiliations(): HasMany
    {
        return $this->hasMany(GroupAffiliation::class, 'parent_group_id');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'group_content_links', 'group_id', 'content_id')
            ->where('group_content_links.content_type', 'event')
            ->where('group_content_links.status', 'accepted')
            ->withTimestamps();
    }

    public function canReceiveProposalFrom(int $userId): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->visibility === GroupVisibility::Public || $this->is_listed) {
            return true;
        }

        return $this->hasActiveMember($userId);
    }

    public function chatThread(): HasOne
    {
        return $this->hasOne(ChatThread::class);
    }

    public function chatThreads(): HasMany
    {
        return $this->hasMany(ChatThread::class);
    }

    public function hasActiveMember(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->where('status', GroupMemberStatus::Active)
            ->exists();
    }

    public function hasActiveRole(int $userId, array $roles): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->where('status', GroupMemberStatus::Active)
            ->whereIn('role', array_map(fn ($role) => $role instanceof GroupMemberRole ? $role->value : $role, $roles))
            ->exists();
    }

    public function activeAdminsCount(): int
    {
        return $this->members()
            ->where('status', GroupMemberStatus::Active)
            ->where('role', GroupMemberRole::Admin)
            ->count();
    }

    public function scopeListed($query)
    {
        return $query->where('is_active', true)
            ->where('is_listed', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        $tokens = preg_split('/\s+/u', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $like = '%'.addcslashes(mb_strtolower($token), '%_\\').'%';
            $query->where(function ($groups) use ($like) {
                $groups->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(slug) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(description, ?)) LIKE ?', ['', $like]);
            });
        }

        return $query;
    }
}
