<?php

namespace Domain\User\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Domain\Profile\Models\Profile;
use Laravel\Sanctum\HasApiTokens;
use Domain\User\Enums\AccountType;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Domain\Poetry\Models\Poem;
use Domain\Engagement\Models\Like;
use Domain\Engagement\Models\Bookmark;
use Domain\Notification\Models\Notification;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'public_id',
        'email',
        'password',
        'status',
        'email_verified_at',
        'account_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'account_type' => AccountType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->public_id)) {
                $user->public_id = (string) Str::uuid();
            }
            if (empty($user->status)) {
                $user->status = 'active';
            }
        });

        static::created(function (self $user) {
            if (! $user->relationLoaded('profile')) {
                $user->load('profile');
            }

            if (! $user->profile) {
                $baseNickname = 'user' . $user->public_id;
                $baseNickname = strtolower(str_replace('-', '', $baseNickname));
                $nickname = substr($baseNickname, 0, 24);

                $counter = 1;

                while (Profile::query()->where('nickname', $nickname)->exists()) {
                    $suffix = (string) $counter;
                    $nickname = substr($baseNickname, 0, 24 - strlen($suffix)) . $suffix;
                    $counter++;
                }

                $user->profile()->create([
                    'nickname' => $nickname,
                    'display_name' => null,
                    'preferred_locale' => 'it',
                    'is_public' => true,
                ]);
            }
        });
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withTimestamps();
    }



    /**Helpers */
    public function isAudience(): bool
    {
        return $this->account_type === AccountType::Audience;
    }

    public function isPoetOrganizer(): bool
    {
        return $this->account_type === AccountType::PoetOrganizer;
    }

    public function hasRole(string $role): bool
{
    return $this->roles()->where('name', $role)->exists();
}

public function isModerator(): bool
{
    return $this->hasRole('moderator');
}

public function isAdmin(): bool
{
    return $this->hasRole('admin');
}

public function poems(): HasMany
{
    return $this->hasMany(Poem::class);
}

public function likes(): MorphMany
{
    return $this->morphMany(Like::class, 'likeable');
}

public function bookmarks(): MorphMany
{
    return $this->morphMany(Bookmark::class, 'bookmarkable');
}

public function notifications(): HasMany
{
    return $this->hasMany(Notification::class);
}
}
