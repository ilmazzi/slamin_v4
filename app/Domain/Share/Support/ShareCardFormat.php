<?php

namespace Domain\Share\Support;

enum ShareCardFormat: string
{
    case Square = 'square';
    case Portrait = 'portrait';
    case Story = 'story';
    case Landscape = 'landscape';

    public function width(): int
    {
        return match ($this) {
            self::Square, self::Portrait, self::Story => 1080,
            self::Landscape => 1200,
        };
    }

    public function height(): int
    {
        return match ($this) {
            self::Square => 1080,
            self::Portrait => 1350,
            self::Story => 1920,
            self::Landscape => 630,
        };
    }
}
