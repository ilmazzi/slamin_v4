<?php

namespace Domain\Profile\Support;

use Domain\Profile\Models\Profile;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Storage;

final class ProfilePayload
{
    /**
     * @return array{bio: bool, location: bool, website_url: bool, email: bool}
     */
    public static function defaultVisibility(): array
    {
        return [
            'bio' => true,
            'location' => true,
            'website_url' => true,
            'email' => false,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array{bio: bool, location: bool, website_url: bool, email: bool}
     */
    public static function visibility(?array $stored): array
    {
        $defaults = self::defaultVisibility();
        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            'bio' => array_key_exists('bio', $stored) ? (bool) $stored['bio'] : $defaults['bio'],
            'location' => array_key_exists('location', $stored) ? (bool) $stored['location'] : $defaults['location'],
            'website_url' => array_key_exists('website_url', $stored) ? (bool) $stored['website_url'] : $defaults['website_url'],
            'email' => array_key_exists('email', $stored) ? (bool) $stored['email'] : $defaults['email'],
        ];
    }

    public static function fileUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    public static function avatarUrl(?string $path): ?string
    {
        return self::fileUrl($path);
    }

    /**
     * @return array<string, mixed>
     */
    public static function owner(User $user): array
    {
        $user->loadMissing('profile');
        $profile = $user->profile;

        return [
            'public_id' => $profile?->public_id,
            'nickname' => $profile?->nickname,
            'display_name' => $profile?->display_name,
            'bio' => $profile?->bio,
            'location' => $profile?->location,
            'website_url' => $profile?->website_url,
            'preferred_locale' => $profile?->preferred_locale,
            'is_public' => (bool) ($profile?->is_public ?? false),
            'visibility' => self::visibility($profile?->visibility),
            'avatar_url' => self::fileUrl($profile?->avatar_path),
            'banner_url' => self::fileUrl($profile?->banner_path),
            'email' => $user->email,
            'is_owner' => true,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function public(?Profile $profile, ?User $viewer): ?array
    {
        if (! $profile) {
            return null;
        }

        $isOwner = $viewer !== null && (int) $viewer->id === (int) $profile->user_id;
        if (! $profile->is_public && ! $isOwner) {
            return null;
        }

        $profile->loadMissing('user');

        if ($isOwner) {
            return self::owner($profile->user);
        }

        $visibility = self::visibility($profile->visibility);

        return [
            'public_id' => $profile->public_id,
            'nickname' => $profile->nickname,
            'display_name' => $profile->display_name,
            'bio' => $visibility['bio'] ? $profile->bio : null,
            'location' => $visibility['location'] ? $profile->location : null,
            'website_url' => $visibility['website_url'] ? $profile->website_url : null,
            'email' => $visibility['email'] ? $profile->user?->email : null,
            'avatar_url' => self::avatarUrl($profile->avatar_path),
            'is_public' => true,
            'is_owner' => false,
        ];
    }
}
