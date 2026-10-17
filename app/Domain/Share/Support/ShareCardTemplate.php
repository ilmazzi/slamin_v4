<?php

namespace Domain\Share\Support;

enum ShareCardTemplate: string
{
    case Poster = 'poster';
    case Typographic = 'typographic';
    case Brand = 'brand';
    case Verse = 'verse';
    case Promo = 'promo';
    case PhotoClean = 'photo_clean';
    case PhotoLogo = 'photo_logo';

    /**
     * @return list<self>
     */
    public static function forContentType(string $contentType): array
    {
        return match ($contentType) {
            'event', 'article' => [self::Poster, self::Typographic, self::Brand],
            'gig' => [self::Promo, self::Typographic, self::Brand],
            'poem' => [self::Verse, self::Typographic, self::Brand],
            'media' => [self::PhotoClean, self::PhotoLogo, self::Brand],
            default => [self::Brand],
        };
    }

    public static function defaultFor(string $contentType): self
    {
        return match ($contentType) {
            'event', 'article' => self::Poster,
            'gig' => self::Promo,
            'poem' => self::Verse,
            'media' => self::PhotoLogo,
            default => self::Brand,
        };
    }

    public static function resolve(string $contentType, mixed $value): self
    {
        $template = self::tryFrom(trim((string) $value)) ?? self::defaultFor($contentType);

        return in_array($template, self::forContentType($contentType), true)
            ? $template
            : self::defaultFor($contentType);
    }

    public static function fromRequest(string $contentType, mixed $value, ?string $routeName = null): self
    {
        if (is_string($routeName) && str_ends_with($routeName, '.preview')) {
            return self::defaultFor($contentType);
        }

        return self::resolve($contentType, $value);
    }

    public function usesImage(): bool
    {
        return in_array($this, [self::Poster, self::Promo, self::PhotoClean, self::PhotoLogo], true);
    }
}
