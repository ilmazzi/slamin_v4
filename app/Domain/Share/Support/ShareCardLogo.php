<?php

namespace Domain\Share\Support;

use GdImage;
use RuntimeException;

/**
 * Official Slam In wordmark for share cards: white letters + red mark.
 *
 * Raster source of frontend/src/assets/slamin-logo-white.svg.
 */
final class ShareCardLogo
{
    public const SOURCE_WIDTH = 184;

    public const SOURCE_HEIGHT = 94;

    public const DISPLAY_WIDTH = 220;

    public const MARGIN_TOP = 40;

    public const MARGIN_RIGHT = 48;

    public const TEXT_GAP = 28;

    public static function displayHeight(): int
    {
        return (int) round(self::DISPLAY_WIDTH * (self::SOURCE_HEIGHT / self::SOURCE_WIDTH));
    }

    public static function reservedRight(): int
    {
        return self::MARGIN_RIGHT + self::DISPLAY_WIDTH + self::TEXT_GAP;
    }

    public static function stamp(GdImage $canvas): void
    {
        $logo = self::load();
        $destW = self::DISPLAY_WIDTH;
        $destH = self::displayHeight();
        $destX = imagesx($canvas) - self::MARGIN_RIGHT - $destW;
        $destY = self::MARGIN_TOP;

        imagealphablending($canvas, true);
        imagecopyresampled(
            $canvas,
            $logo,
            $destX,
            $destY,
            0,
            0,
            $destW,
            $destH,
            imagesx($logo),
            imagesy($logo),
        );
        imagedestroy($logo);
    }

    private static function load(): GdImage
    {
        $path = resource_path('brand/slamin-logo-white.png');
        if (! is_file($path)) {
            throw new RuntimeException('Missing share-card logo: slamin-logo-white.png');
        }

        $logo = @imagecreatefrompng($path);
        if (! $logo instanceof GdImage) {
            throw new RuntimeException('Unable to load the Slam In share-card logo.');
        }

        imagealphablending($logo, false);
        imagesavealpha($logo, true);

        return $logo;
    }
}
