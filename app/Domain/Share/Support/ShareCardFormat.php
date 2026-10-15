<?php

namespace Domain\Share\Support;

enum ShareCardFormat: string
{
    case Square = 'square';
    case Landscape = 'landscape';

    public function width(): int
    {
        return match ($this) {
            self::Square => 1080,
            self::Landscape => 1200,
        };
    }

    public function height(): int
    {
        return match ($this) {
            self::Square => 1080,
            self::Landscape => 630,
        };
    }
}
