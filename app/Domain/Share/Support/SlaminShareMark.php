<?php

namespace Domain\Share\Support;

use GdImage;

/**
 * Canonical Slam In mark: frontend/src/components/brand/SlaminMark.jsx (viewBox 27×39).
 */
final class SlaminShareMark
{
    public const WIDTH = 27.0;

    public const HEIGHT = 39.0;

    public const BODY_HEIGHT = 29.3142;

    public const NOTCH_X = 12.2315;

    public static function totalHeight(int $bodyHeight): int
    {
        return (int) round($bodyHeight * (self::HEIGHT / self::BODY_HEIGHT));
    }

    public static function fill(GdImage $image, int $x, int $y, int $width, int $height, int $bodyHeight, int $color): void
    {
        $bodyBottom = $y + $bodyHeight;
        $notchX = $x + (int) round($width * (self::NOTCH_X / self::WIDTH));

        imagefilledpolygon($image, [
            $x, $y,
            $x + $width, $y,
            $x + $width, $y + $height,
            $notchX, $bodyBottom,
            $x, $bodyBottom,
        ], $color);
    }
}
