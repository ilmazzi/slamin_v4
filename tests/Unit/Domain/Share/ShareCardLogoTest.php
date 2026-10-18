<?php

use Domain\Share\Support\ShareCardLogo;
use Tests\TestCase;

uses(TestCase::class);

it('uses dark letters when stamped on a white background', function () {
    $canvas = imagecreatetruecolor(1080, 1080);
    imagefilledrectangle($canvas, 0, 0, 1079, 1079, imagecolorallocate($canvas, 255, 255, 255));

    ShareCardLogo::stamp($canvas);

    [$letterX, $letterY, $markX, $markY] = shareCardLogoSamplePoints(1080, 1080);
    $letter = imagecolorat($canvas, $letterX, $letterY);
    $mark = imagecolorat($canvas, $markX, $markY);
    imagedestroy($canvas);

    expect(($letter >> 16) & 0xFF)->toBeLessThan(40)
        ->and(($letter >> 8) & 0xFF)->toBeLessThan(40)
        ->and($letter & 0xFF)->toBeLessThan(40);

    expect(($mark >> 16) & 0xFF)->toBeGreaterThan(210)
        ->and(($mark >> 8) & 0xFF)->toBeLessThan(20)
        ->and($mark & 0xFF)->toBeLessThan(40);
});
