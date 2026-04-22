<?php

namespace Domain\Group\Models;

use Domain\Chat\Models\ChatThread;
use Domain\Event\Models\Event;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
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
        'is_active',
    ];

    protected $casts = [
        'visibility' => GroupVisibility::class,
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
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

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_group')
            ->withTimestamps();
    }

    public function chatThread(): HasOne
    {
        return $this->hasOne(ChatThread::class);
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

    public function activeOwnersCount(): int
    {
        return $this->members()
            ->where('status', GroupMemberStatus::Active)
            ->where('role', GroupMemberRole::Owner)
            ->count();
    }

    public function scopeVisibleToPublic($query)
    {
        return $query->where('is_active', true)
            ->where('visibility', GroupVisibility::PublicOpen);
    }
}
