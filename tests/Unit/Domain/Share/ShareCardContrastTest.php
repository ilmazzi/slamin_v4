<?php

use Domain\Share\Support\ShareCardContrast;
use Tests\TestCase;

uses(TestCase::class);

it('treats mint and orange as light and media black as dark', function () {
    expect(ShareCardContrast::isLightRgb(132, 218, 180))->toBeTrue()
        ->and(ShareCardContrast::isLightRgb(253, 174, 95))->toBeTrue()
        ->and(ShareCardContrast::isLightRgb(101, 116, 222))->toBeFalse()
        ->and(ShareCardContrast::isLightRgb(0, 0, 0))->toBeFalse();
});

it('samples a canvas region to decide if the background is light', function () {
    $canvas = imagecreatetruecolor(200, 200);
    imagefilledrectangle($canvas, 0, 0, 199, 199, imagecolorallocate($canvas, 255, 255, 255));
    imagefilledrectangle($canvas, 0, 0, 199, 99, imagecolorallocate($canvas, 20, 20, 20));

    expect(ShareCardContrast::isLightBehind($canvas, 20, 120, 160, 60))->toBeTrue()
        ->and(ShareCardContrast::isLightBehind($canvas, 20, 10, 160, 60))->toBeFalse();

    imagedestroy($canvas);
});
