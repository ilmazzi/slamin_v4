<?php

namespace Domain\Profile\Support;

final class SocialLinks
{
    public const PLATFORMS = [
        'instagram',
        'facebook',
        'youtube',
        'tiktok',
        'twitter',
        'bluesky',
        'mastodon',
    ];

    /**
     * @param  array<int, mixed>|null  $links
     * @return list<array{platform: string, label: string, url: string}>
     */
    public static function normalize(?array $links): array
    {
        $normalized = [];
        $seenPlatforms = [];

        foreach ($links ?? [] as $link) {
            if (! is_array($link)) {
                continue;
            }

            $platform = strtolower(trim((string) ($link['platform'] ?? 'custom')));
            $url = trim((string) ($link['url'] ?? ''));
            $label = trim((string) ($link['label'] ?? ''));

            if (
                $url === ''
                || ! filter_var($url, FILTER_VALIDATE_URL)
                || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ) {
                continue;
            }

            if ($platform !== 'custom') {
                if (! in_array($platform, self::PLATFORMS, true) || isset($seenPlatforms[$platform])) {
                    continue;
                }

                $seenPlatforms[$platform] = true;
                $label = ucfirst($platform === 'twitter' ? 'X / Twitter' : $platform);
            } elseif ($label === '') {
                continue;
            }

            $normalized[] = [
                'platform' => $platform,
                'label' => $label,
                'url' => $url,
            ];
        }

        return array_slice($normalized, 0, 20);
    }
}
