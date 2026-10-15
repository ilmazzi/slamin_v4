<?php

namespace Domain\Profile\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Profile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'profiles';

    protected $fillable = [
        'user_id',
        'public_id',
        'display_name',
        'nickname',
        'bio',
        'location',
        'website_url',
        'social_links',
        'preferred_locale',
        'is_public',
        'avatar_path',
        'banner_path',
        'visibility',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'visibility' => 'array',
        'social_links' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $profile) {
            if (empty($profile->public_id)) {
                $profile->public_id = (string) Str::uuid();
            }

            if (empty($profile->preferred_locale)) {
                $profile->preferred_locale = 'it';
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
