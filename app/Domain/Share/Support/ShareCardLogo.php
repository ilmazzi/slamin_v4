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

    public const MARGIN_LEFT = 48;

    public const MARGIN_BOTTOM = 40;

    public const TEXT_GAP = 28;

    public static function displayHeight(): int
    {
        return (int) round(self::DISPLAY_WIDTH * (self::SOURCE_HEIGHT / self::SOURCE_WIDTH));
    }

    public static function destX(): int
    {
        return self::MARGIN_LEFT;
    }

    public static function destY(int $canvasHeight): int
    {
        return $canvasHeight - self::MARGIN_BOTTOM - self::displayHeight();
    }

    public static function stamp(GdImage $canvas): void
    {
        self::stampAt($canvas, self::destX(), self::destY(imagesy($canvas)));
    }

    public static function stampCentered(GdImage $canvas): void
    {
        $x = (int) round((imagesx($canvas) - self::DISPLAY_WIDTH) / 2);
        $y = (int) round((imagesy($canvas) - self::displayHeight()) / 2);

        self::stampAt($canvas, $x, $y);
    }

    public static function stampAt(GdImage $canvas, int $x, int $y, ?bool $onLight = null): void
    {
        $onLight ??= self::isLightBehind($canvas, $x, $y);
        $logo = self::load($onLight);
        $destW = self::DISPLAY_WIDTH;
        $destH = self::displayHeight();

        imagealphablending($canvas, true);
        imagecopyresampled(
            $canvas,
            $logo,
            $x,
            $y,
            0,
            0,
            $destW,
            $destH,
            imagesx($logo),
            imagesy($logo),
        );
        imagedestroy($logo);
    }

    private static function isLightBehind(GdImage $canvas, int $x, int $y): bool
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $samples = [
            [$x + 24, $y + 24],
            [$x + 90, $y + 36],
            [$x + 150, $y + 48],
            [$x + 40, $y + 70],
        ];
        $light = 0;
        $count = 0;
        foreach ($samples as [$sampleX, $sampleY]) {
            if ($sampleX < 0 || $sampleY < 0 || $sampleX >= $width || $sampleY >= $height) {
                continue;
            }
            $color = imagecolorsforindex($canvas, imagecolorat($canvas, $sampleX, $sampleY));
            $luma = (0.299 * $color['red']) + (0.587 * $color['green']) + (0.114 * $color['blue']);
            $count++;
            if ($luma >= 210) {
                $light++;
            }
        }

        return $count > 0 && $light >= (int) ceil($count / 2);
    }

    private static function load(bool $onLight): GdImage
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

        if ($onLight) {
            self::recolorLettersForLightBackground($logo);
        }

        return $logo;
    }

    private static function recolorLettersForLightBackground(GdImage $logo): void
    {
        $width = imagesx($logo);
        $height = imagesy($logo);
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorsforindex($logo, imagecolorat($logo, $x, $y));
                if ($color['alpha'] >= 120) {
                    continue;
                }
                if ($color['red'] > 180 && $color['green'] < 90 && $color['blue'] < 90) {
                    continue;
                }
                if ($color['red'] > 200 && $color['green'] > 200 && $color['blue'] > 200) {
                    imagesetpixel(
                        $logo,
                        $x,
                        $y,
                        imagecolorallocatealpha($logo, 17, 17, 17, $color['alpha']),
                    );
                }
            }
        }
    }
}
