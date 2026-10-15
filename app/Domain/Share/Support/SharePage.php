<?php

namespace Domain\Share\Support;

use Illuminate\Http\Request;

final class SharePage
{
    private const CRAWLER_PATTERN = '/facebookexternalhit|Facebot|Twitterbot|WhatsApp|TelegramBot|Slackbot|LinkedInBot|Discordbot|Pinterest|Googlebot|bingbot|Applebot|Iframely|vkShare|redditbot|Embedly|SkypeUriPreview/i';

    public static function isCrawler(Request $request): bool
    {
        return (bool) preg_match(self::CRAWLER_PATTERN, (string) $request->userAgent());
    }

    public static function excerpt(?string $text, int $limit = 180): string
    {
        $excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');

        if (mb_strlen($excerpt) > $limit) {
            return rtrim(mb_substr($excerpt, 0, $limit - 3)).'…';
        }

        return $excerpt;
    }
}
