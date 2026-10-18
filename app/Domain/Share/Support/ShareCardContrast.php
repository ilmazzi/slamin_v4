<?php

namespace Domain\Share\Support;

use GdImage;

final class ShareCardContrast
{
    public const LIGHT_LUMA = 160;

    public static function luma(int $red, int $green, int $blue): float
    {
        return (0.299 * $red) + (0.587 * $green) + (0.114 * $blue);
    }

    public static function isLightRgb(int $red, int $green, int $blue): bool
    {
        return self::luma($red, $green, $blue) >= self::LIGHT_LUMA;
    }

    public static function isLightBehind(GdImage $canvas, int $x, int $y, int $width, int $height): bool
    {
        $canvasWidth = imagesx($canvas);
        $canvasHeight = imagesy($canvas);
        $x = max(0, min($x, $canvasWidth - 1));
        $y = max(0, min($y, $canvasHeight - 1));
        $width = max(1, min($width, $canvasWidth - $x));
        $height = max(1, min($height, $canvasHeight - $y));

        $light = 0;
        $count = 0;
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 4; $col++) {
                $sampleX = $x + (int) round(($width - 1) * (($col + 0.5) / 4));
                $sampleY = $y + (int) round(($height - 1) * (($row + 0.5) / 3));
                $color = imagecolorsforindex($canvas, imagecolorat($canvas, $sampleX, $sampleY));
                $count++;
                if (self::isLightRgb($color['red'], $color['green'], $color['blue'])) {
                    $light++;
                }
            }
        }

        return $count > 0 && $light >= (int) ceil($count / 2);
    }
}
