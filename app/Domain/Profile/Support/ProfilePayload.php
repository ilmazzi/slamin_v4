<?php

namespace Domain\Profile\Support;

use Domain\Profile\Models\Profile;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Storage;

final class ProfilePayload
{
    /**
     * Public profile content sections, in display order.
     */
    public const SECTIONS = ['poems', 'events', 'articles', 'media'];

    /**
     * @return array<string, bool>
     */
    public static function defaultVisibility(): array
    {
        return [
            'bio' => true,
            'location' => true,
            'website_url' => true,
            'email' => false,
            ...array_fill_keys(self::SECTIONS, true),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, bool>
     */
    public static function visibility(?array $stored): array
    {
        $defaults = self::defaultVisibility();
        if (! is_array($stored)) {
            return $defaults;
        }

        foreach ($defaults as $key => $default) {
            $defaults[$key] = array_key_exists($key, $stored) ? (bool) $stored[$key] : $default;
        }

        return $defaults;
    }

    /**
     * @param  array<string, bool>  $visibility
     * @return list<string>
     */
    public static function sections(array $visibility): array
    {
        return array_values(array_filter(self::SECTIONS, fn (string $section) => $visibility[$section] ?? true));
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
        $visibility = self::visibility($profile?->visibility);

        return [
            'public_id' => $profile?->public_id,
            'nickname' => $profile?->nickname,
            'display_name' => $profile?->display_name,
            'bio' => $profile?->bio,
            'location' => $profile?->location,
            'website_url' => $profile?->website_url,
            'social_links' => SocialLinks::normalize($profile?->social_links),
            'preferred_locale' => $profile?->preferred_locale,
            'is_public' => (bool) ($profile?->is_public ?? false),
            'visibility' => $visibility,
            'sections' => self::sections($visibility),
            'avatar_url' => self::fileUrl($profile?->avatar_path),
            'banner_url' => self::fileUrl($profile?->banner_path),
            'email' => $user->email,
            'account_type' => $user->account_type?->value,
            'member_since' => $user->created_at?->toIso8601String(),
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
            'social_links' => SocialLinks::normalize($profile->social_links),
            'email' => $visibility['email'] ? $profile->user?->email : null,
            'avatar_url' => self::avatarUrl($profile->avatar_path),
            'banner_url' => self::fileUrl($profile->banner_path),
            'account_type' => $profile->user?->account_type?->value,
            'member_since' => $profile->user?->created_at?->toIso8601String(),
            'sections' => self::sections($visibility),
            'is_public' => true,
            'is_owner' => false,
        ];
    }
}
