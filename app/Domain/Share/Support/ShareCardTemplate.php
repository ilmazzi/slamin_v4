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
    case DateCut = 'date_cut';
    case Framed = 'framed';

    /**
     * @return list<self>
     */
    public static function catalog(): array
    {
        return [
            self::Poster,
            self::DateCut,
            self::Framed,
            self::Typographic,
            self::Promo,
            self::Verse,
            self::PhotoClean,
            self::PhotoLogo,
        ];
    }

    /**
     * @return list<self>
     */
    public static function forContentType(string $contentType): array
    {
        unset($contentType);

        return self::catalog();
    }

    public static function defaultFor(string $contentType): self
    {
        return match ($contentType) {
            'event', 'article' => self::Poster,
            'gig' => self::Promo,
            'poem' => self::Verse,
            'media' => self::PhotoLogo,
            default => self::Poster,
        };
    }

    public function isGraphic(): bool
    {
        return in_array($this, [
            self::DateCut,
            self::Framed,
            self::PhotoClean,
            self::PhotoLogo,
            self::Promo,
        ], true);
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
        return in_array($this, [
            self::Poster,
            self::Promo,
            self::PhotoClean,
            self::PhotoLogo,
            self::Framed,
        ], true);
    }
}
